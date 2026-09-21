(function (global) {
  global.LIBCONTROL_DOCUMENTATION = {
    intro: 'Step-by-step guides for owners, branch managers, and front-desk staff using LibControl every day.',
    sections: [
      {
        id: 'getting-started',
        title: 'Getting started',
        icon: 'fa-rocket',
        summary: 'Login, navigation, and daily workflow basics.',
        topics: [
          {
            id: 'gs-login',
            title: 'Admin login & roles',
            steps: [
              'Open your library’s LibControl URL in Chrome, Edge, or Safari.',
              'Sign in with your work email and password.',
              'Owners see full menus; branch managers land on their branch dashboard.',
              'Use the profile menu to update your password or sign out.',
            ],
          },
          {
            id: 'gs-navigation',
            title: 'Understanding the sidebar',
            steps: [
              'Dashboard — today’s numbers, enquiries, and expiring plans.',
              'Seat Map & Trial Seats — live desk operations.',
              'Students & Fees — registry and billing.',
              'Finance — expenses and combined statement.',
              'Help & Support — articles, documentation, and tickets.',
            ],
          },
        ],
      },
      {
        id: 'seat-operations',
        title: 'Seat operations',
        icon: 'fa-th-large',
        summary: 'Halls, seat maps, assignments, and transfers.',
        topics: [
          {
            id: 'so-halls',
            title: 'Halls and capacity',
            steps: [
              'Owners create halls under Branches and set seat capacity.',
              'Each hall gets its own seat map layout.',
              'Opening hours and branch settings apply to reporting scope.',
            ],
          },
          {
            id: 'so-assign',
            title: 'Assigning and releasing seats',
            steps: [
              'Pick the hall on the seat map.',
              'Select a vacant seat → choose student → confirm.',
              'To release, open the occupied seat and end the assignment.',
              'Use Seat Assignments for a list view and bulk review.',
            ],
          },
        ],
      },
      {
        id: 'students-trials',
        title: 'Students & trials',
        icon: 'fa-user-graduate',
        summary: 'Registration, profiles, and trial conversion.',
        topics: [
          {
            id: 'st-register',
            title: 'Registering students',
            steps: [
              'Add students from Students → Add Student or convert an enquiry.',
              'Email and phone are required fields for contact and notifications.',
              'Student code and ID card options are available after save.',
            ],
          },
          {
            id: 'st-trial',
            title: 'Trial workflow',
            steps: [
              'Create a trial from Trial Seats with start/end dates.',
              'Assign a trial seat on the map — it shows separately from paid seats.',
              'Before expiry, contact the student and convert to a fee plan or release the seat.',
            ],
          },
        ],
      },
      {
        id: 'fees-finance',
        title: 'Fees & finance',
        icon: 'fa-indian-rupee-sign',
        summary: 'Plans, payments, renewals, and statements.',
        topics: [
          {
            id: 'ff-plans',
            title: 'Fee plans & payments',
            steps: [
              'Set Up Fee links a student to a plan amount and date range.',
              'Toggle Receive Payment when money is collected at the desk.',
              'Record partial payments later from the fee row actions.',
            ],
          },
          {
            id: 'ff-renew',
            title: 'Renewals & expiries',
            steps: [
              'Dashboard highlights plans expiring in the next 7 days.',
              'Use Renew on the fee row to extend dates and collect payment.',
              'Enable email reminders in Settings when SMTP is configured.',
            ],
          },
          {
            id: 'ff-finance',
            title: 'Finance & statement',
            steps: [
              'Log expenses under Finance → Expenses.',
              'Open the Statement tab to see fee income and expenses together.',
              'Use date filters when reviewing a month-end summary.',
            ],
          },
        ],
      },
      {
        id: 'settings-support',
        title: 'Settings & support',
        icon: 'fa-life-ring',
        summary: 'Configuration, notifications, and getting help.',
        topics: [
          {
            id: 'ss-settings',
            title: 'Library settings',
            steps: [
              'General — library name, code, and regional preferences.',
              'Website — public library site content when enabled.',
              'Email — SMTP and student notification toggles.',
              'Branch managers may only edit allowed tabs for their branch.',
            ],
          },
          {
            id: 'ss-help',
            title: 'Help resources',
            steps: [
              'Support Articles — quick answers to common questions.',
              'Support Documentation — structured guides (this page).',
              'Help & Support in the app — create a ticket with attachments.',
              'Contact Phenomit by phone, email, or WhatsApp for onboarding help.',
            ],
          },
        ],
      },
    ],
  };
})(window);
