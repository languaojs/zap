@extends('layouts.base')

@push('styles')
<style>
    :root {
        --zap-ink: #14231f;
        --zap-muted: #61716b;
        --zap-green: #a5f36b;
        --zap-line: #e4ebe5;
        --zap-paper: #f7faf6;
    }

    body {
        margin: 0;
        background: var(--zap-paper);
        color: var(--zap-ink);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .zap-navbar {
        position: relative;
        z-index: 1;
        background: #fff;
        border-bottom: 1px solid var(--zap-line);
    }

    .zap-navbar__inner,
    .zap-footer__inner {
        width: min(1120px, calc(100% - 48px));
        margin: 0 auto;
    }

    .zap-navbar__inner {
        min-height: 76px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    .zap-brand {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        color: var(--zap-ink);
        text-decoration: none;
    }

    .zap-brand__mark {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background: var(--zap-ink);
        color: var(--zap-green);
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -2px;
        transform: skew(-5deg);
    }

    .zap-brand__name {
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .zap-brand__name span {
        color: #718078;
        font-weight: 500;
    }

    .zap-navbar__links {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .zap-navbar__links a,
    .zap-footer__link {
        color: #3d5148;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        transition: color 160ms ease, background 160ms ease, transform 160ms ease;
    }

    .zap-navbar__links a:hover,
    .zap-footer__link:hover {
        color: #187344;
    }

    .zap-navbar__links .zap-navbar__cta {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 11px 17px;
        border-radius: 9px;
        background: var(--zap-ink);
        color: white;
    }

    .zap-navbar__cta:hover {
        background: #24513c;
        color: white !important;
        transform: translateY(-1px);
    }

    .zap-home {
        overflow: hidden;
        padding: 72px 24px 92px;
    }

    .zap-home__hero {
        position: relative;
        isolation: isolate;
        width: min(1120px, 100%);
        min-height: 440px;
        box-sizing: border-box;
        margin: 0 auto;
        padding: clamp(38px, 7vw, 78px);
        display: grid;
        grid-template-columns: minmax(0, 1.12fr) minmax(280px, .88fr);
        align-items: center;
        gap: clamp(32px, 6vw, 72px);
        border-radius: 22px;
        background:
            radial-gradient(ellipse at 88% 15%, rgb(165 243 107 / 13%), transparent 31%),
            linear-gradient(135deg, #14231f 0%, #192d25 100%);
        color: #fff;
        box-shadow: 0 24px 60px rgb(20 35 31 / 12%);
    }

    .zap-home__hero::before,
    .zap-home__hero::after {
        position: absolute;
        z-index: -1;
        content: "";
        border: 1px solid rgb(220 255 212 / 10%);
        border-radius: 50%;
    }

    .zap-home__hero::before {
        width: 440px;
        height: 440px;
        right: -170px;
        top: -250px;
    }

    .zap-home__hero::after {
        width: 330px;
        height: 330px;
        right: -115px;
        top: -195px;
    }

    .zap-home__copy {
        max-width: 590px;
    }

    .zap-home__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        margin: 0 0 24px;
        color: #c7d7ce;
        font-size: 12px;
        font-weight: 750;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .zap-home__eyebrow::before {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--zap-green);
        box-shadow: 0 0 15px rgb(165 243 107 / 65%);
        content: "";
    }

    .zap-home__title {
        max-width: 580px;
        margin: 0;
        color: white;
        font-size: clamp(42px, 6vw, 68px);
        font-weight: 760;
        letter-spacing: -4px;
        line-height: 1.03;
    }

    .zap-home__title span {
        color: var(--zap-green);
    }

    .zap-home__description {
        max-width: 490px;
        margin: 22px 0 0;
        color: #c4d1ca;
        font-size: 16px;
        line-height: 1.75;
    }

    .zap-home__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin-top: 32px;
    }

    .zap-button {
        display: inline-flex;
        min-height: 46px;
        box-sizing: border-box;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 0 18px;
        border: 1px solid transparent;
        border-radius: 9px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 160ms ease, background 160ms ease, border-color 160ms ease;
    }

    .zap-button:hover {
        transform: translateY(-2px);
    }

    .zap-button--primary {
        background: var(--zap-green);
        color: #14231f;
    }

    .zap-button--primary:hover {
        background: #bbff8d;
    }

    .zap-button--secondary {
        border-color: rgb(255 255 255 / 24%);
        color: #f4f8f5;
    }

    .zap-button--secondary:hover {
        border-color: rgb(255 255 255 / 52%);
        background: rgb(255 255 255 / 6%);
    }

    .zap-terminal {
        position: relative;
        padding: 21px;
        border: 1px solid rgb(221 241 228 / 15%);
        border-radius: 15px;
        background: rgb(9 20 15 / 52%);
        box-shadow: 0 22px 45px rgb(0 0 0 / 15%);
        backdrop-filter: blur(8px);
    }

    .zap-terminal__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 17px;
        border-bottom: 1px solid rgb(221 241 228 / 12%);
        color: #8fa59a;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .zap-terminal__dots {
        display: flex;
        gap: 5px;
    }

    .zap-terminal__dots i {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #426253;
    }

    .zap-terminal__content {
        padding-top: 18px;
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
        font-size: 13px;
        line-height: 2;
    }

    .zap-terminal__line {
        display: flex;
        gap: 10px;
        color: #a8bab0;
    }

    .zap-terminal__line strong {
        color: var(--zap-green);
        font-weight: 500;
    }

    .zap-terminal__success {
        margin: 14px 0 0;
        padding: 12px 13px;
        border-radius: 8px;
        background: rgb(165 243 107 / 8%);
        color: #d9f5c9;
        font-size: 12px;
        line-height: 1.6;
    }

    .zap-home__next {
        width: min(930px, 100%);
        margin: 64px auto 0;
    }

    .zap-home__section-heading {
        margin-bottom: 24px;
        text-align: center;
    }

    .zap-home__section-heading p {
        margin: 0 0 9px;
        color: #418257;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.6px;
        text-transform: uppercase;
    }

    .zap-home__section-heading h2 {
        margin: 0;
        color: var(--zap-ink);
        font-size: clamp(25px, 3vw, 32px);
        letter-spacing: -1.3px;
    }

    .zap-home__cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 15px;
    }

    .zap-home__card {
        min-height: 135px;
        box-sizing: border-box;
        padding: 22px;
        border: 1px solid var(--zap-line);
        border-radius: 13px;
        background: #fff;
    }

    .zap-home__card-index {
        display: inline-grid;
        width: 27px;
        height: 27px;
        place-items: center;
        border-radius: 8px;
        background: #eef8e9;
        color: #317449;
        font-size: 11px;
        font-weight: 800;
    }

    .zap-home__card h3 {
        margin: 16px 0 6px;
        color: var(--zap-ink);
        font-size: 15px;
        letter-spacing: -.2px;
    }

    .zap-home__card p {
        margin: 0;
        color: var(--zap-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .zap-footer {
        border-top: 1px solid var(--zap-line);
        background: #fff;
    }

    .zap-footer__inner {
        min-height: 86px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 18px 0;
        box-sizing: border-box;
    }

    .zap-brand--footer .zap-brand__mark {
        width: 29px;
        height: 29px;
        border-radius: 9px;
        font-size: 17px;
    }

    .zap-brand--footer .zap-brand__name {
        font-size: 16px;
    }

    .zap-footer__inner p {
        margin: 0;
        color: var(--zap-muted);
        font-size: 13px;
    }

    .zap-footer__link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    @media (max-width: 760px) {
        .zap-home {
            padding: 38px 18px 64px;
        }

        .zap-home__hero {
            grid-template-columns: 1fr;
            gap: 38px;
            padding: 42px 30px;
        }

        .zap-home__title {
            letter-spacing: -2.7px;
        }

        .zap-terminal {
            max-width: 440px;
        }

        .zap-home__cards {
            grid-template-columns: 1fr;
        }

        .zap-home__card {
            min-height: auto;
        }

        .zap-footer__inner {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 480px) {
        .zap-navbar__inner,
        .zap-footer__inner {
            width: min(100% - 32px, 1120px);
        }

        .zap-navbar__inner {
            min-height: 68px;
        }

        .zap-navbar__links {
            gap: 14px;
        }

        .zap-navbar__links a {
            font-size: 12px;
        }

        .zap-navbar__links .zap-navbar__cta {
            gap: 7px;
            padding: 10px 12px;
        }

        .zap-home__hero {
            padding: 34px 23px;
            border-radius: 16px;
        }

        .zap-home__title {
            font-size: 42px;
        }

        .zap-home__description {
            font-size: 14px;
        }

        .zap-terminal {
            padding: 17px;
        }

        .zap-footer__inner {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
        }
    }
</style>
@endpush

@section('content')
<main class="zap-home">
    <section class="zap-home__hero" aria-labelledby="welcome-title">
        <div class="zap-home__copy">
            <p class="zap-home__eyebrow">Your framework is ready</p>
            <h1 class="zap-home__title" id="welcome-title">Thanks for installing <span>Zap PHP.</span></h1>
            <p class="zap-home__description">
                Congratulations! Your new framework is up and running. The next great thing you build starts with a clean slate.
            </p>
            <div class="zap-home__actions">
                <a class="zap-button zap-button--primary" href="https://zap.zainurrahman.my.id" target="_blank" rel="noopener noreferrer">
                    Explore the documentation <span aria-hidden="true">&rarr;</span>
                </a>
                <a class="zap-button zap-button--secondary" href="#next-steps">What to do next</a>
            </div>
        </div>

        <div class="zap-terminal" aria-label="Zap PHP installation successful">
            <div class="zap-terminal__top">
                <span>zapphp / your-project</span>
                <span class="zap-terminal__dots" aria-hidden="true"><i></i><i></i><i></i></span>
            </div>
            <div class="zap-terminal__content" aria-hidden="true">
                <div class="zap-terminal__line"><span>$</span><strong>framework status</strong></div>
                <div class="zap-terminal__line"><span>&gt;</span><span>Zap PHP installed</span></div>
                <div class="zap-terminal__line"><span>&gt;</span><span>Application ready</span></div>
                <p class="zap-terminal__success">All set. It's your turn to make something useful.</p>
            </div>
        </div>
    </section>

    <section class="zap-home__next" id="next-steps" aria-labelledby="next-title">
        <div class="zap-home__section-heading">
            <p>A good place to begin</p>
            <h2 id="next-title">Make this project yours.</h2>
        </div>
        <div class="zap-home__cards">
            <article class="zap-home__card">
                <span class="zap-home__card-index" aria-hidden="true">01</span>
                <h3>Find your way around</h3>
                <p>Get familiar with the project structure and the tools available to you.</p>
            </article>
            <article class="zap-home__card">
                <span class="zap-home__card-index" aria-hidden="true">02</span>
                <h3>Build your first route</h3>
                <p>Start shaping your application from <code>routes/web.php</code>.</p>
            </article>
            <article class="zap-home__card">
                <span class="zap-home__card-index" aria-hidden="true">03</span>
                <h3>Keep learning</h3>
                <p>Follow the guides and references in the Zap PHP documentation.</p>
            </article>
        </div>
    </section>
</main>
@endsection
