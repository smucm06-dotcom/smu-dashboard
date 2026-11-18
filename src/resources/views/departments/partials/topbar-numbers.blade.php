{{--
    The banner's cards numbered 1, 2, 3, 4, as on the initiative pages (psrp_shared/findings-styles).
    Included by the departments list and each department's page, in their styles, so only their banner
    changes.
--}}
<style>
    .topbar-stats-grid { counter-reset: dept-card; }
    .topbar-stat-card { counter-increment: dept-card; }
    .topbar-stat-label { display: flex; align-items: center; gap: 7px; }
    .topbar-stat-label::before { content: counter(dept-card); flex: none; width: 19px; height: 19px; border-radius: 50%; background: #a9ecc8; color: #063d28; font-size: 11px; font-weight: 800; letter-spacing: 0; display: inline-flex; align-items: center; justify-content: center; }
    /* On phones the cards are narrow, so the number sits above the label. */
    @media (max-width: 600px) {
        .topbar-stat-label { display: block; }
        .topbar-stat-label::before { display: flex; width: 14px; height: 14px; font-size: 8.5px; margin-bottom: 3px; }
    }
</style>
