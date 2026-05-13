<style>
/* ── Hard viewport containment — nothing renders wider than the screen ── */
/* Use overflow-x:clip (not hidden) — `hidden` breaks `position:sticky` on descendants. */
html, body            { overflow-x: clip; max-width: 100vw; width: 100%; }
@supports not (overflow-x: clip) {
    html, body        { overflow-x: hidden; }
}
*                     { box-sizing: border-box; }
*                     { min-width: 0; }

/* ── Sidebar: nav scrolls internally so logo stays at top and logout stays at bottom ── */
.sidebar              { overflow: hidden !important; }
.sidebar > nav        { flex: 1 1 auto; min-height: 0; overflow-y: auto; }

/* ── History month label: never sticky — it covers the first card's date ── */
.timeline-month-label {
    position: static !important;
    top: auto !important;
    z-index: auto !important;
}

/* ── Mobile-first: default = single column, expand to multi-column at desktop ── */
.dash-grid            { display: grid; grid-template-columns: 1fr; gap: 1.25rem; max-width: 100%; }
.history-grid         { display: grid; grid-template-columns: 1fr; gap: 24px; max-width: 100%; }
.dash-grid > *,
.history-grid > *     { min-width: 0; max-width: 100%; }
.balance-card-body    { flex-direction: column; align-items: stretch; }
.balance-card-body > div { width: 100%; min-width: 0; }
.hero-amount-row      { flex-wrap: wrap; gap: 12px; }
.card                 { max-width: 100%; overflow: hidden; }

/* ── Mobile-only refinements (≤ 900px) ── */
@media (max-width: 900px) {
    .app-shell                    { grid-template-columns: 1fr !important; }
    .app-shell > div              { min-width: 0; max-width: 100vw; }
    .app-shell > div > main       { padding: 20px 16px 56px !important; max-width: 100% !important; width: 100% !important; }
    .history-receipt-panel        { display: none !important; }
    .page-header                  { flex-direction: column !important; align-items: flex-start !important; gap: 16px !important; }
    .page-header > div            { width: 100% !important; }
    .page-header > div:last-child { flex-wrap: wrap !important; }
    h1[style*="font-size:32px"]   { font-size: 22px !important; }
    h1[style*="font-size:28px"]   { font-size: 20px !important; }

    .card, .card * { max-width: 100%; }
    .topbar { padding: 0 16px !important; }
    /* Hide long user name in admin topbar — keep just the avatar */
    .topbar > div:last-child > span:not(.chip) { display: none !important; }

    /* Support page: release fixed-height constraints so the page scrolls */
    .support-grid {
        grid-template-columns: 1fr !important;
        height: auto !important;
        max-height: none !important;
        min-height: 0 !important;
    }
    .support-chat        { overflow: visible !important; }
    .support-messages    { overflow-y: visible !important; max-height: 60vh; }
    .support-aside       { overflow: visible !important; }

    /* History timeline card row: stack content over amount/receipt vertically */
    .timeline-card-row {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .timeline-card-row > div:last-child {
        text-align: left !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        padding-top: 8px !important;
        border-top: 1px solid var(--line) !important;
    }
    .timeline-card-row > div:last-child > div:first-child {
        font-size: 13px !important;
    }

    /* Admin KYC buyer row: stack avatar / info / properties / actions */
    .kyc-buyer-row {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    .kyc-buyer-row > * {
        width: 100% !important;
        text-align: left !important;
        margin-right: 0 !important;
    }
    .kyc-buyer-row .avatar {
        width: 44px !important;
    }
    .kyc-buyer-row > div:nth-child(3) {
        font-size: 12px !important;
        color: var(--fg-mute) !important;
        padding-top: 8px !important;
        border-top: 1px solid var(--line) !important;
    }

    /* Generic admin tables: scroll horizontally inside a card */
    .card table { min-width: 560px; }
    .card:has(table) { overflow-x: auto !important; }
}

/* ── Desktop-only: restore multi-column grids ── */
@media (min-width: 901px) {
    .dash-grid    { grid-template-columns: 1.4fr 1fr; }
    .history-grid { grid-template-columns: 1fr 360px; }
    .balance-card-body { flex-direction: row; align-items: flex-end; }
    .balance-card-body > div { width: auto; }
}
</style>
