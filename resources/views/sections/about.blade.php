<section id="rdsa" class="rdsa-about">

        <div class="container">
            <div class="row align-items-center g-5">

                {{-- Présentation --}}
                <div class="col-lg-4">

                    <div class="rdsa-about-intro">

                        <span class="section-kicker">
                            {{ $siteContent['about_kicker'] }}
                        </span>

                        <h2>
                            @php($heading = \App\Models\SiteContent::heading($siteContent['about_heading']))
                            {{ $heading[0] }} <span>{{ $heading[1] }}</span>
                        </h2>

                        <div class="section-line"></div>

                        <p>
                            {{ $siteContent['about_description'] }}
                        </p>

                        @if($siteContent['show_participer'])<a href="#participer" class="rdsa-outline-button">
                            {{ $siteContent['about_button'] }}
                        <i class="bi bi-arrow-right"></i>
                        </a>@endif


                    </div>

                </div>


                {{-- Les 4 axes --}}
                <div class="col-lg-8">

                    <div class="row g-0 rdsa-values">

                        {{-- 1 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_1_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_1_text'])) !!}
                                </p>

                            </article>

                        </div>


                        {{-- 2 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-fire"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_2_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_2_text'])) !!}
                                </p>

                            </article>

                        </div>


                        {{-- 3 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-book"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_3_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_3_text'])) !!}
                                </p>

                            </article>

                        </div>


                        {{-- 4 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-heart"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_4_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_4_text'])) !!}
                                </p>

                            </article>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>
