@php
$contactEdition = clone ($edition ?? new \App\Models\Edition);
$contactEdition->contact_phone = $contactEdition->contact_phone ?: ($siteContent['general_phone'] ?? null);
$contactEdition->contact_whatsapp = $contactEdition->contact_whatsapp ?: ($siteContent['general_whatsapp'] ?? null);
$contactEdition->venue = $contactEdition->venue ?: ($siteContent['general_venue'] ?: null);
$contactEdition->map_url = $contactEdition->map_url ?: ($siteContent['general_map_url'] ?: null);
@endphp
<section id="contact" class="rdsa-contact">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="contact-intro">
                    <span class="section-kicker">{{ $siteContent['contact_kicker'] }}</span>
                    <h2>@php($heading = \App\Models\SiteContent::heading($siteContent['contact_heading'])){{ $heading[0] }} <span>{{ $heading[1] }}</span></h2>
                    <div class="section-line"></div>
                    <p>{{ $siteContent['contact_description'] }}</p>
                </div>
                <div class="contact-info-list">
                    @if($contactEdition?->contact_phone)
                    <a class="contact-info-item" href="tel:{{ $contactEdition->contact_phone }}">
                        <div class="contact-icon"><i class="bi bi-telephone"></i></div>
                        <div><span>TÉLÉPHONE</span><strong>{{ $contactEdition->contact_phone }}</strong></div>
                    </a>
                    @else
                    <div class="contact-info-item"><div class="contact-icon"><i class="bi bi-telephone"></i></div><div><span>TÉLÉPHONE</span><strong>Numéro à venir</strong></div></div>
                    @endif
                    @if($contactEdition?->contact_whatsapp)
                    <a class="contact-info-item" href="https://wa.me/{{ ltrim($contactEdition->contact_whatsapp, '+') }}" target="_blank" rel="noopener noreferrer">
                        <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
                        <div><span>WHATSAPP</span><strong>Nous écrire sur WhatsApp</strong></div>
                    </a>
                    @else
                    <div class="contact-info-item"><div class="contact-icon"><i class="bi bi-whatsapp"></i></div><div><span>WHATSAPP</span><strong>Contact à venir</strong></div></div>
                    @endif
                    <div class="contact-info-item"><div class="contact-icon"><i class="bi bi-geo-alt"></i></div><div><span>LIEU</span><strong>{{ $contactEdition?->venue ?? 'Lieu à confirmer' }}</strong></div></div>
                </div>
            </div>
            <div class="col-lg-7">
                @php($location = \App\Support\EditionLocation::data($contactEdition))
                <div class="contact-map contact-map-live">
                    @if($location && $location['embed'])
                    <iframe title="Carte du lieu : {{ $contactEdition->venue }}" src="{{ $location['embed'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @else
                    <div class="map-placeholder"><i class="bi bi-geo-alt-fill"></i><p>Le lieu de la rencontre sera précisé prochainement.</p></div>
                    @endif
                    <div class="contact-map-caption">
                        <div><h3>{{ $contactEdition?->venue ?? 'Lieu à confirmer' }}</h3><p>{{ $contactEdition?->venue_details }}</p>
                            @if($location && !$location['precise'])
                            <small>Carte recherchée à partir de l’adresse du lieu.</small>
                            @endif
                        </div>
                        @if($location)
                        <a class="map-button" href="{{ $location['directions'] }}" target="_blank" rel="noopener noreferrer">VOIR L’ITINÉRAIRE <i class="bi bi-arrow-up-right"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
