<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTicketRecords;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase, CreatesTicketRecords;

    private function submit(array $changes = [])
    {
        $token = $this->get('/')->assertOk()->viewData('contactSubmissionToken');
        return $this->postJson('/messages', array_merge([
            'submission_token' => $token, 'name' => 'Visiteur test', 'email' => 'visiteur@example.test',
            'subject' => 'Question sur le programme', 'body' => 'À quelle heure faut-il arriver pour la rencontre ?',
        ], $changes));
    }

    public function test_submission_is_private_and_retry_does_not_create_duplicate(): void
    {
        $response = $this->submit()->assertOk()->assertJsonStructure(['conversation_url', 'reference', 'next_token']);
        $message = ContactMessage::firstOrFail();
        $this->get($response->json('conversation_url'))->assertOk()->assertSee('Question sur le programme')
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertNotSame($message->access_token, DB::table('contact_messages')->value('access_token'));
        $this->assertArrayNotHasKey('access_token', $message->toArray());
        $this->postJson('/messages', ['submission_token' => $message->submission_token, 'name' => $message->name,
            'email' => $message->email, 'subject' => $message->subject, 'body' => $message->body])->assertOk();
        $this->assertDatabaseCount('contact_messages', 1);
        $this->get('/messages/' . $message->reference . '/' . str_repeat('0', 48))->assertNotFound();
        $this->get('/messages/' . $message->reference)->assertNotFound();
    }

    public function test_invalid_contact_and_honeypot_are_rejected(): void
    {
        $this->submit(['email' => '', 'phone' => ''])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
        $this->submit(['website' => 'spam.example'])->assertUnprocessable()->assertJsonValidationErrors('website');
        $this->submit(['submission_token' => '00000000-0000-4000-8000-000000000000'])->assertUnprocessable()->assertJsonValidationErrors('submission_token');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_read_reply_followup_and_counter_work_without_external_provider(): void
    {
        $this->submit()->assertOk();
        $message = ContactMessage::firstOrFail();
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->getJson('/admin/messages/count')->assertOk()->assertJson(['count' => 1]);
        $this->get('/admin/messages/' . $message->id)->assertOk();
        $this->assertSame('read', $message->fresh()->status);
        $this->getJson('/admin/messages/count')->assertJson(['count' => 0]);
        $this->postJson('/admin/messages/' . $message->id . '/reply', ['body' => 'La rencontre commence à 08 h.'])->assertOk();
        $this->assertSame('done', $message->fresh()->status);
        $this->getJson($message->privateUrl())->assertOk()->assertJsonPath('version', 'done:1');
        $this->get($message->privateUrl())->assertSee('La rencontre commence à 08 h.');
        $this->postJson(route('messages.reply', ['reference' => $message->reference, 'token' => $message->access_token]), ['body' => 'Merci, peut-on venir plus tôt ?', 'author_type' => 'admin'])->assertOk();
        $this->assertSame('new', $message->fresh()->status);
        $this->assertSame('visitor', $message->replies()->reorder()->latest('id')->first()->author_type);
        $this->getJson('/admin/messages/count')->assertJson(['count' => 1]);
    }

    public function test_admin_inbox_is_protected_and_searchable_and_text_is_escaped(): void
    {
        $this->submit(['subject' => '<script>alert(1)</script>'])->assertOk();
        $message = ContactMessage::firstOrFail();
        $this->get('/admin/messages')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->post('/admin/messages/' . $message->id . '/reply', ['body' => 'Non autorisé'])->assertForbidden();
        $this->actingAs($this->makeAdmin())->get('/admin/messages?q=introuvable')->assertOk()->assertDontSee($message->reference);
        $this->get('/admin/messages?q=Visiteur')->assertOk()->assertSee($message->reference)->assertDontSee('<script>alert(1)</script>', false);
        $this->get($message->privateUrl())->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
