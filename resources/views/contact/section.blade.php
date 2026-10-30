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
                    @if($edition?->contact_phone)
                    <a class="contact-info-item" href="tel:{{ $edition->contact_phone }}">
                        <div class="contact-icon"><i class="bi bi-telephone"></i></div>
                        <div><span>TÉLÉPHONE</span><strong>{{ $edition->contact_phone }}</strong></div>
                    </a>
                    @else
                    <div class="contact-info-item"><div class="contact-icon"><i class="bi bi-telephone"></i></div><div><span>TÉLÉPHONE</span><strong>Numéro à venir</strong></div></div>
                    @endif
                    @if($edition?->contact_whatsapp)
                    <a class="contact-info-item" href="https://wa.me/{{ ltrim($edition->contact_whatsapp, '+') }}" target="_blank" rel="noopener noreferrer">
                        <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
                        <div><span>WHATSAPP</span><strong>Nous écrire sur WhatsApp</strong></div>
                    </a>
                    @else
                    <div class="contact-info-item"><div class="contact-icon"><i class="bi bi-whatsapp"></i></div><div><span>WHATSAPP</span><strong>Contact à venir</strong></div></div>
                    @endif
                    <div class="contact-info-item"><div class="contact-icon"><i class="bi bi-geo-alt"></i></div><div><span>LIEU</span><strong>{{ $edition?->venue ?? 'Lieu à confirmer' }}</strong></div></div>
                </div>
            </div>
            <div class="col-lg-7">
                @php($location = \App\Support\EditionLocation::data($edition))
                <div class="contact-map contact-map-live">
                    @if($location && $location['embed'])
                    <iframe title="Carte du lieu : {{ $edition->venue }}" src="{{ $location['embed'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @else
                    <div class="map-placeholder"><i class="bi bi-geo-alt-fill"></i><p>Le lieu de la rencontre sera précisé prochainement.</p></div>
                    @endif
                    <div class="contact-map-caption">
                        <div><h3>{{ $edition?->venue ?? 'Lieu à confirmer' }}</h3><p>{{ $edition?->venue_details }}</p>
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
