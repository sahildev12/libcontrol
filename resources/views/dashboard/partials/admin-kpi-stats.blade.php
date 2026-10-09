<div class="lc-dash-stats">
    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Total Branches</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['branches']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Total Students</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['students']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Total Seats</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <x-admin.kpi-chair-icon />
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['seats']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Monthly Revenue</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            </span>
        </div>
        <p class="lc-dash-stat__value" title="₹{{ number_format($kpis['monthly_revenue']) }}">₹{{ number_format($kpis['monthly_revenue']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Occupied Seats</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['occupied']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Available Seats</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <x-admin.kpi-chair-icon />
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['available']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Trial Seats</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['on_trial']) }}</p>
    </article>

    <article class="lc-dash-stat">
        <div class="lc-dash-stat__head">
            <p class="lc-dash-stat__label">Expired Seats</p>
            <span class="lc-dash-stat__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 11h-4"/></svg>
            </span>
        </div>
        <p class="lc-dash-stat__value">{{ number_format($kpis['expired_seats']) }}</p>
    </article>
</div>
