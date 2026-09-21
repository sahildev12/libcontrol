(function (global) {
  global.LIBCONTROL_SUPPORT_ARTICLES = {
    categories: [
      { id: 'all', label: 'All topics' },
      { id: 'getting-started', label: 'Getting started' },
      { id: 'seats', label: 'Seats & halls' },
      { id: 'students', label: 'Students & trials' },
      { id: 'fees', label: 'Fees & finance' },
      { id: 'account', label: 'Account & access' },
      { id: 'troubleshooting', label: 'Troubleshooting' },
    ],
    articles: [
      {
        id: 'what-is-libcontrol',
        category: 'getting-started',
        title: 'What is LibControl?',
        summary: 'A quick overview of what LibControl does for reading libraries and study centres.',
        body: [
          'LibControl is web-based software for reading libraries and study centres. It helps you manage seat maps, student records, trial seats, fee plans, renewals, and — on multi-branch plans — every branch from one owner dashboard.',
          'Staff use LibControl in a web browser on a computer or phone. There is no separate app to install on the front desk PC.',
        ],
      },
      {
        id: 'first-login',
        category: 'getting-started',
        title: 'How do I log in for the first time?',
        summary: 'Find your login URL, username, and password after onboarding.',
        body: [
          'After setup, your Phenomit contact shares the admin login URL for your installation. Bookmark this link for daily use.',
          'Use the email and password created during onboarding. If you forget your password, use “Forgot password” on the login screen or contact your library owner / Phenomit support.',
          'Branch managers log in at the same URL but only see menus for their assigned branch.',
        ],
      },
      {
        id: 'assign-seat',
        category: 'seats',
        title: 'How do I assign a seat to a student?',
        summary: 'Use the live seat map to pick an available seat and link it to a student.',
        body: [
          'Open Seat Map from the sidebar and select the correct hall.',
          'Click an available seat on the map. Choose the student (or create one first under Students).',
          'Confirm the assignment. The seat colour updates immediately so other staff can see it is occupied.',
          'To move a student, use the transfer / change seat action from the seat or from Seat Assignments.',
        ],
      },
      {
        id: 'trial-seats',
        category: 'students',
        title: 'How do trial seats work?',
        summary: 'Offer short trial periods without mixing trial students into regular seat counts.',
        body: [
          'Create a trial booking from Trial Seats or directly from the seat map when marking a seat as trial.',
          'Set the trial start and end dates. LibControl tracks remaining days and upcoming expiries.',
          'When the trial converts, move the student to a regular fee plan. When it ends without conversion, release the seat so it becomes available again.',
        ],
      },
      {
        id: 'setup-fee',
        category: 'fees',
        title: 'How do I set up a student fee plan?',
        summary: 'Create plans, record received payments, and track renewals.',
        body: [
          'Go to Fee Management and click Set Up Fee. Pick the student, hall, seat (if applicable), plan dates, and amount.',
          'Use the Receive Payment section when the student pays at the time of setup — enter amount, method, and date.',
          'Pending or partial payments stay visible on the fee list until you record the balance.',
          'Use renew actions before a plan expires to extend the student without losing seat history.',
        ],
      },
      {
        id: 'finance-overview',
        category: 'fees',
        title: 'Where do I see income and expenses together?',
        summary: 'Use Finance for fee income, expenses, and the combined statement.',
        body: [
          'Open Finance from the sidebar. The main view shows expenses; fee income appears in insights and on the Statement tab.',
          'The Statement page combines fee collections and expenses so owners can review cash movement for a period.',
          'Branch managers see numbers for their branch only; owners with multiple branches can switch scope where enabled.',
        ],
      },
      {
        id: 'branch-manager-access',
        category: 'account',
        title: 'What can a branch manager see?',
        summary: 'Branch staff get a focused menu for daily desk work.',
        body: [
          'Branch managers typically see dashboard, seat map, students, trials, fees, enquiries, and branch settings — not platform-wide deployment or developer tools.',
          'They work inside one branch context. Switch branch only appears if your account is allowed multiple branches.',
          'If a menu item is missing, ask your library owner to confirm your role and branch assignment.',
        ],
      },
      {
        id: 'email-notifications',
        category: 'account',
        title: 'How do student email notifications work?',
        summary: 'Welcome, birthday, offers, and recovery emails need SMTP configured.',
        body: [
          'Owners configure SMTP and notification toggles under Settings → Email notifications.',
          'Welcome emails send when a new student is added (if enabled). Birthday and recovery campaigns use the student email on file — email is required on registration forms.',
          'If emails do not send, verify SMTP credentials, sender address, and that the student has a valid email.',
        ],
      },
      {
        id: 'create-support-ticket',
        category: 'troubleshooting',
        title: 'How do I create a support ticket?',
        summary: 'Use Help & Support inside your LibControl admin panel.',
        body: [
          'Open Help & Support from the sidebar. Fill in category, priority, subject, and a clear description.',
          'Attach screenshots if helpful (images or PDF, up to 5 files). Submit — you can track status under Your recent tickets.',
          'For urgent login or billing issues, also email or WhatsApp Phenomit using the contact details on this site.',
        ],
      },
      {
        id: 'seat-double-booking',
        category: 'troubleshooting',
        title: 'A seat looks occupied but the student left — what now?',
        summary: 'Release or transfer the assignment from Seat Map or Seat Assignments.',
        body: [
          'Open the seat on the map and release the assignment, or end the booking from Seat Assignments.',
          'If the student still has an active fee plan, renewing or closing the plan may be a separate step under Fee Management.',
          'Refresh the page if another staff member just made a change — the map updates on load.',
        ],
      },
      {
        id: 'sync-license',
        category: 'troubleshooting',
        title: 'Why do I see a license or sync warning?',
        summary: 'Usually connectivity or an expired plan — contact Phenomit if it persists.',
        body: [
          'Licensed installations phone home periodically to validate the deployment. A temporary network issue can show a grace-period message.',
          'Ensure the server has outbound HTTPS access. If the warning remains after 24 hours, open a support ticket with your library name and domain.',
        ],
      },
    ],
  };
})(window);
