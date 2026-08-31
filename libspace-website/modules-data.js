(function (global) {
  global.LIBSPACE_MODULES = {
    order: ['branch', 'seats', 'trials', 'students', 'fees', 'expenses'],
    items: {
      branch: {
        id: 'branch',
        tabLabel: 'Branch & Halls',
        icon: 'fa-building',
        title: 'Branch & Halls',
        description: 'Manage your entire library network from one place. Add branches, create halls, configure seat capacity, set operating hours, assign staff, and monitor branch-level performance.',
        capabilities: [
          'Create and manage multiple branches',
          'Create multiple halls within each branch',
          'Configure seat capacity and numbering',
          'Set opening and closing hours',
          'Assign branch staff and access permissions',
          'View branch-level occupancy and performance'
        ],
        features: [
          { icon: 'fa-building-columns', title: 'Centralized Branch Management', text: 'Manage all your library locations from one place.' },
          { icon: 'fa-chair', title: 'Halls & Seat Configuration', text: 'Create halls and configure capacity and seat numbering.' },
          { icon: 'fa-clock', title: 'Operating Hours', text: 'Set opening, closing, and break hours for every hall.' },
          { icon: 'fa-user-shield', title: 'Staff & Access Control', text: 'Assign staff and manage their permissions.' },
          { icon: 'fa-chart-line', title: 'Branch Performance Overview', text: 'Track occupancy, revenue, and student activity.' }
        ],
        highlightTitle: 'One view. Complete control.',
        highlightText: 'Manage your entire library network without switching between registers, spreadsheets, or separate systems.'
      },
      seats: {
        id: 'seats',
        tabLabel: 'Seat Map',
        icon: 'fa-th-large',
        title: 'Seat Map',
        description: 'Get a clear view of every seat in every hall and know exactly which seats are available, occupied, assigned, or being used for trials.',
        capabilities: [
          'View seats by branch and hall',
          'See available and occupied seats',
          'Assign students to seats',
          'Change seat assignments',
          'Identify trial seats',
          'Quickly check seat status'
        ],
        features: [
          { icon: 'fa-border-all', title: 'Visual Seat Layout', text: 'Understand your hall capacity at a glance.' },
          { icon: 'fa-circle-dot', title: 'Live Seat Status', text: 'See which seats are available, occupied, assigned, or on trial.' },
          { icon: 'fa-bolt', title: 'Quick Assignment', text: 'Assign students to available seats without complicated steps.' },
          { icon: 'fa-right-left', title: 'Seat Management', text: 'Change or release assignments whenever required.' },
          { icon: 'fa-chart-pie', title: 'Occupancy Visibility', text: 'Understand how efficiently each hall is being utilized.' }
        ],
        highlightTitle: 'Know every seat. Anytime.',
        highlightText: 'Give your staff a clear view of the entire seating operation.'
      },
      trials: {
        id: 'trials',
        tabLabel: 'Trial Seats',
        icon: 'fa-hourglass-half',
        title: 'Trial Seats',
        description: 'Manage trial students from their first day until conversion, while keeping trial seats and expiry dates under control.',
        capabilities: [
          'Create trial students',
          'Assign temporary seats',
          'Set trial start and end dates',
          'Track remaining trial days',
          'Monitor upcoming expirations',
          'Convert trials into regular students'
        ],
        features: [
          { icon: 'fa-calendar-days', title: 'Trial Period Tracking', text: 'Know exactly when every trial starts and ends.' },
          { icon: 'fa-chair', title: 'Temporary Seat Assignment', text: 'Keep trial seats organized without affecting regular assignments.' },
          { icon: 'fa-bell', title: 'Expiry Alerts', text: 'Identify trials that are about to expire.' },
          { icon: 'fa-user-check', title: 'Conversion Management', text: 'Convert successful trials into regular students.' },
          { icon: 'fa-door-open', title: 'Seat Release', text: 'Keep seats available after a trial ends.' }
        ],
        highlightTitle: 'Never lose track of a trial.',
        highlightText: 'Know who is on trial, which seat they\'re using, and what happens next.'
      },
      students: {
        id: 'students',
        tabLabel: 'Students',
        icon: 'fa-user-graduate',
        title: 'Students',
        description: 'Keep every student\'s profile, seat assignment, membership information, documents, and activity organized in one place.',
        capabilities: [
          'Create and manage student profiles',
          'Generate student codes',
          'Assign seats',
          'Manage student status',
          'Store required documents',
          'Search and filter students',
          'View student information and history'
        ],
        features: [
          { icon: 'fa-id-card', title: 'Complete Student Profiles', text: 'Keep important student information organized.' },
          { icon: 'fa-chair', title: 'Seat Assignment', text: 'See which seat and hall belongs to each student.' },
          { icon: 'fa-file-lines', title: 'Student Documents', text: 'Keep required documents accessible from the student profile.' },
          { icon: 'fa-magnifying-glass', title: 'Search & Filters', text: 'Find students quickly using useful filters.' },
          { icon: 'fa-clock-rotate-left', title: 'Student History', text: 'Access relevant membership and activity information.' }
        ],
        highlightTitle: 'Every student. One organized record.',
        highlightText: 'Replace scattered registers and spreadsheets with structured student information.'
      },
      fees: {
        id: 'fees',
        tabLabel: 'Fees',
        icon: 'fa-wallet',
        title: 'Fees',
        description: 'Track fee collections, pending payments, renewals, and overdue accounts without relying on manual registers.',
        capabilities: [
          'Record fee payments',
          'Track pending fees',
          'Track overdue payments',
          'View upcoming renewals',
          'View payment history',
          'Monitor collections',
          'Manage fee records'
        ],
        features: [
          { icon: 'fa-indian-rupee-sign', title: 'Fee Collection Tracking', text: 'Know exactly how much has been collected.' },
          { icon: 'fa-circle-exclamation', title: 'Pending & Overdue Fees', text: 'Quickly identify payments that need attention.' },
          { icon: 'fa-calendar-check', title: 'Renewal Tracking', text: 'See which students are approaching their next payment date.' },
          { icon: 'fa-receipt', title: 'Payment History', text: 'Review previous payments for each student.' },
          { icon: 'fa-chart-column', title: 'Collection Overview', text: 'Understand your fee activity across the library.' }
        ],
        highlightTitle: 'No missed fees. No guesswork.',
        highlightText: 'Know what has been paid, what is due, and what needs attention.'
      },
      expenses: {
        id: 'expenses',
        tabLabel: 'Expenses',
        icon: 'fa-receipt',
        title: 'Expenses',
        description: 'Keep your library\'s operating expenses organized and understand where your money is going.',
        capabilities: [
          'Add expenses',
          'Categorize expenses',
          'Record expense amounts',
          'Add notes',
          'Track expense dates',
          'Filter expense records',
          'Review expense history'
        ],
        features: [
          { icon: 'fa-pen-to-square', title: 'Expense Recording', text: 'Record every operational expense in one place.' },
          { icon: 'fa-tags', title: 'Categories', text: 'Organize spending into meaningful categories.' },
          { icon: 'fa-building', title: 'Branch-level Tracking', text: 'Understand expenses across different branches.' },
          { icon: 'fa-clock-rotate-left', title: 'Expense History', text: 'Review previous spending whenever required.' },
          { icon: 'fa-eye', title: 'Spending Visibility', text: 'Get a clearer picture of where your library\'s money is going.' }
        ],
        highlightTitle: 'Know where every rupee goes.',
        highlightText: 'Keep operational spending organized and visible.'
      }
    }
  };
})(typeof window !== 'undefined' ? window : globalThis);
