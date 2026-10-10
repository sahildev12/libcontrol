(function (global) {
  global.LIBCONTROL_DOCUMENTATION = {
    intro: 'Step-by-step guides for owners, branch managers, and front-desk staff using LibControl every day. Type a question like “how to add a student” to find the right steps.',
    sections: [
      {
        id: 'getting-started',
        title: 'Getting started',
        icon: 'fa-rocket',
        summary: 'Login, passwords, the sidebar, and the dashboard.',
        topics: [
          {
            id: 'gs-login',
            title: 'Logging in (admin and branch)',
            keywords: ['sign in', 'login page', 'admin login', 'branch login', 'portal', 'cannot login'],
            steps: [
              'Open your library’s LibControl link in Chrome, Edge, or Safari and bookmark it.',
              'Library owners use the Admin log in; branch managers use the Branch log in. Use the “Use admin login” / “Use branch login” link to switch.',
              'Enter your Email and Password, tick “Remember me” on your own device, and sign in.',
              'Branch managers sign in with the email and password set for their branch under Branch.',
            ],
          },
          {
            id: 'gs-password',
            title: 'Forgot or change your password',
            keywords: ['reset password', 'forgot password', 'change password', 'update password', 'locked out'],
            steps: [
              'Forgot it? Click “Forgot password?” on the login page, enter your email, and click “Email Password Reset Link”.',
              'Open the email, choose a new password, and click “Reset Password”.',
              'To change it while logged in, open the account menu (top right) → My Profile → Update Password.',
              'Branch passwords can also be reset by the owner from Branch → View → Reset Password.',
            ],
          },
          {
            id: 'gs-navigation',
            title: 'Understanding the sidebar',
            keywords: ['menu', 'navigation', 'where is', 'find page'],
            steps: [
              'Dashboard — today’s numbers, enquiries, and plans expiring soon.',
              'Halls, Seats, and Trial Seats — your seat map and desk operations.',
              'Students and Fee Management — student records, plans, and payments.',
              'Finance — income, expenses, and the statement. Promotion, Grow My Library, and Enquiries help you get new students.',
              'Settings, My Profile, and Help & Support are in the account menu at the top right.',
            ],
          },
          {
            id: 'gs-dashboard',
            title: 'Reading the dashboard',
            keywords: ['dashboard', 'overview', 'today', 'revenue', 'occupied seats', 'vacant seats', 'statistics'],
            steps: [
              'Owners see totals for branches, students, seats, monthly revenue, and occupied / available / trial / expired seats.',
              'Branch managers see Total Seats, Occupied, Vacant, On Trial, and Today’s Overview (new enquiries, new students, today’s revenue, expiring plans).',
              'Use the date range picker and Refresh to change the period.',
              '“Expiring Plans (Next 7 Days)” lets you renew a student straight from the dashboard.',
            ],
          },
        ],
      },
      {
        id: 'students',
        title: 'Students',
        icon: 'fa-user-graduate',
        summary: 'Add, import, edit, and manage students and ID cards.',
        topics: [
          {
            id: 'st-add',
            title: 'How to add a new student',
            keywords: ['add student', 'new student', 'create student', 'register student', 'admission', 'enrol student', 'student form'],
            steps: [
              'Open Students from the sidebar and click “Add Student”.',
              'Fill in Full Name, Gender, Type (Trial or Regular), and Date of Birth.',
              'Under Contact & Family, enter the 10-digit Phone and Email (your library may require both).',
              'Optionally add Guardian / Father’s Name, Address, “Referred by” student ID, Photo, and ID proof.',
              'Click “Add Student”. A student code is created automatically using your Student ID settings.',
            ],
          },
          {
            id: 'st-self-register',
            title: 'Let students register themselves (QR / link)',
            keywords: ['self registration', 'student register himself', 'registration link', 'qr code', 'student fills form', 'online registration', 'share link'],
            steps: [
              'Click “Add Student” — the blue box at the top shows a registration QR code and link.',
              'Let the student scan the QR or send them the link using “Copy”.',
              'The student fills in their details and clicks “Submit Registration”; they appear in Students immediately.',
              'Each link works once and expires after 2 hours. Open Add Student again for a fresh link.',
            ],
          },
          {
            id: 'st-import',
            title: 'Import students from Excel',
            keywords: ['import', 'excel', 'csv', 'bulk upload', 'upload students', 'spreadsheet', 'old data', 'migrate'],
            steps: [
              'Open Students and click “Import”.',
              'Click “Download sample (.xlsx)” and fill one row per student: Name, Gender, Date of Birth (YYYY-MM-DD) are required; Phone, Email, Father Name, Address, ID Proof Type, Student Type, and Status are optional.',
              'Upload the filled .xlsx, .xls, or .csv file (up to 500 students, 5 MB) and click “Import students”.',
              'If any row has a mistake, nothing is imported and the errors are listed by row number — fix them and upload again.',
            ],
          },
          {
            id: 'st-edit',
            title: 'Edit a student or mark them inactive',
            keywords: ['edit student', 'update student', 'change student phone', 'change student name', 'change details', 'inactive', 'deactivate', 'student left'],
            steps: [
              'In Students, click the pencil icon on the student’s row (or open the student and click “Edit Student”).',
              'Change any details, upload a new photo, or set Status to Inactive when a student leaves.',
              'Click “Save Changes”.',
            ],
          },
          {
            id: 'st-delete',
            title: 'Delete students',
            keywords: ['delete student', 'remove student', 'bulk delete'],
            steps: [
              'In Students, tick the checkboxes of the students you want to remove.',
              'Click “Delete all (N)” and confirm.',
              'Tip: if you may need their history later, set the student to Inactive instead of deleting.',
            ],
          },
          {
            id: 'st-family',
            title: 'Link siblings / family members',
            keywords: ['family', 'sibling', 'brother', 'sister', 'same phone', 'link family'],
            steps: [
              'When adding a student, choose “Link to existing family” under Contact & Family and pick the family member.',
              'Linked students show a Family badge and a Linked Siblings list on their profile.',
              'To separate them, edit the student and click “Unlink from family”.',
            ],
          },
          {
            id: 'st-idcard',
            title: 'Print or download a student ID card',
            keywords: ['id card', 'identity card', 'print card', 'download card', 'student card'],
            steps: [
              'In Students, click the ID card icon on the student’s row (or “ID Card” on their profile).',
              'Click “Download PNG”, “Print card only” (standard 86 × 54 mm card), or “Print this page”.',
              'When printing, turn on “Background graphics” and turn off “Headers and footers”.',
              'Owners choose the card design and logo in Settings → ID Cards.',
            ],
          },
        ],
      },
      {
        id: 'seat-operations',
        title: 'Halls & seats',
        icon: 'fa-chair',
        summary: 'Create halls, assign seats, transfer, and release seats.',
        topics: [
          {
            id: 'so-halls',
            title: 'Create a hall and its seats',
            keywords: ['add hall', 'create hall', 'add seats', 'create seats', 'increase seats', 'more seats', 'capacity', 'room'],
            steps: [
              'Open Halls and click “Add Hall”.',
              'Enter Hall Name and Seat Capacity — seats are created automatically from the capacity.',
              'Optionally tick “Continue seat numbering from another hall” so numbers carry on.',
              'Click “Save”. Edit the hall later to change capacity (it cannot go below seats in use).',
            ],
          },
          {
            id: 'so-map',
            title: 'Seat map colours',
            keywords: ['seat colours', 'legend', 'green seat', 'red seat', 'expiring', 'expired', 'vacant'],
            steps: [
              'Grey = Vacant, Green = Occupied (Full Day), Purple = Occupied (Custom Hours).',
              'Amber = Expiring Soon (within 7 days), Red = Expired.',
              'A small purple dot means a trial student also uses that seat.',
              'Hover over a seat to see its schedule, or click “View Full Schedule”.',
            ],
          },
          {
            id: 'so-assign',
            title: 'Assign a seat to a student',
            keywords: ['assign seat', 'allot seat', 'book seat', 'give seat', 'allocate seat', 'seat booking'],
            steps: [
              'Open Seats, pick the hall, and click a vacant (grey) seat.',
              'Choose the student (or create a new one from the search).',
              'Pick the Time Slot — Full Day or Custom Hours — and set Joining Date and Plan Expiry Date.',
              'Choose Fee Type, Fee Amount, and Full payment or Installments. Turn on “Receive Payment” if they are paying now.',
              'Click “Assign Student”. The seat colour updates for all staff.',
            ],
          },
          {
            id: 'so-transfer',
            title: 'Transfer a student to another seat',
            keywords: ['transfer seat', 'move student', 'change seat', 'shift seat', 'swap seat'],
            steps: [
              'Open Seats and click “Transfer Seat”.',
              'Choose the student, then the new Hall, Seat, and Time Slot.',
              'Click “Preview & Confirm”, then “Confirm Transfer”. Fees and payment history stay the same.',
            ],
          },
          {
            id: 'so-release',
            title: 'Release or cancel a seat',
            keywords: ['release seat', 'cancel seat', 'vacate seat', 'free seat', 'student left', 'end booking'],
            steps: [
              'Click the occupied seat on the map and choose “Cancel Assignment”, then confirm.',
              'Or hover the seat → “View Full Schedule” → “Cancel Booking”.',
              'The seat turns grey (vacant) and can be assigned again.',
            ],
          },
          {
            id: 'so-hours',
            title: 'Library hours and custom time slots',
            keywords: ['timing', 'opening hours', 'closing time', 'shift', 'half day', 'custom hours', '24 hours'],
            steps: [
              'Set opening and closing time (or “Open 24 hours”) in Settings → General → Library hours for each branch.',
              'Full Day covers the whole opening time; Custom Hours lets you pick a start and end time inside it.',
              'Two students can share one seat at different custom hours.',
            ],
          },
        ],
      },
      {
        id: 'trials',
        title: 'Trial seats',
        icon: 'fa-hourglass-half',
        summary: 'Short trials and converting trial students to regular.',
        topics: [
          {
            id: 'tr-create',
            title: 'Give a student a trial seat',
            keywords: ['trial', 'free trial', 'demo seat', 'trial seat', 'try library'],
            steps: [
              'Open Trial Seats and click a seat.',
              'Choose the student, Trial start date, and Duration (1–14 days).',
              'Pick the Time Slot and add an optional trial fee.',
              'Click “Assign Trial”.',
            ],
          },
          {
            id: 'tr-convert',
            title: 'Convert a trial student to regular',
            keywords: ['convert trial', 'trial to regular', 'trial ended', 'join after trial'],
            steps: [
              'Hover over the trial seat and click “View Full Schedule”.',
              'Click “Convert to Regular” and confirm.',
              'Then set up their fee plan in Fee Management.',
            ],
          },
        ],
      },
      {
        id: 'fees-finance',
        title: 'Fees & payments',
        icon: 'fa-indian-rupee-sign',
        summary: 'Fee plans, payments, installments, and renewals.',
        topics: [
          {
            id: 'ff-setup',
            title: 'Set up a fee plan',
            keywords: ['set up fee', 'add fee', 'fee plan', 'monthly fee', 'membership', 'charge student'],
            steps: [
              'The student needs an active seat first (assign one under Seats).',
              'Open Fee Management and click “Set Up Fee”, then choose the student.',
              'Pick Fee Type (Monthly, Yearly, Custom, Membership, One-time), Fee Amount, and the plan dates.',
              'Choose Full payment or Installments, optionally record the payment now, and click “Save”.',
            ],
          },
          {
            id: 'ff-payment',
            title: 'Collect a payment (full or partial)',
            keywords: ['receive payment', 'collect fee', 'record payment', 'partial payment', 'paid half fee', 'balance fee', 'pending fee', 'due', 'cash', 'upi'],
            steps: [
              'In Fee Management, find the student and click “Add fee” on their row.',
              'You will see Total, Paid, and Due. Enter Amount received — partial amounts are fine.',
              'Choose Payment method (Cash, UPI, Card, Bank Transfer, Other) and date, then click “Record payment”.',
              'See every payment under the fee’s “Payment history” tab.',
            ],
          },
          {
            id: 'ff-installments',
            title: 'Installment plans',
            keywords: ['installment', 'emi', 'pay in parts', 'split payment'],
            steps: [
              'In Set Up Fee or Renew plan, choose Payment plan → Installments.',
              'Pick the frequency (Weekly, Monthly, Quarterly, Half-yearly, Yearly, or Flexible), the number of installments (2–12), and the first due date.',
              'Open the fee to see the schedule and click “Mark paid” on each installment as it is collected.',
            ],
          },
          {
            id: 'ff-renew',
            title: 'Renew a student’s plan',
            keywords: ['renew', 'renewal', 'extend plan', 'plan expired', 'expiring'],
            steps: [
              'In Fee Management, the default filter shows plans that are expiring soon or expired.',
              'Click the renew icon (“Renew plan”) on the student’s row.',
              'Set the new period, fee, and payment plan, record the payment if received, and click “Renew plan”.',
              'You can also renew from the seat map (“Renew Plan”) or the dashboard’s expiring list.',
            ],
          },
          {
            id: 'ff-filters',
            title: 'Find unpaid fees and export',
            keywords: ['unpaid', 'overdue', 'outstanding', 'export', 'download fees', 'csv', 'filter fees'],
            steps: [
              'Use the cards at the top: Received This Month, Pending This Month, Overdue, Total Outstanding, Expiring Soon.',
              'Filter by plan status, payment status (Unpaid, Pending, Partial, Paid, Overdue), and date range.',
              'Click “Export CSV” to download the filtered list.',
            ],
          },
        ],
      },
      {
        id: 'finance',
        title: 'Finance & expenses',
        icon: 'fa-chart-line',
        summary: 'Expenses, profit, and the income statement.',
        topics: [
          {
            id: 'fi-expense',
            title: 'Add an expense',
            keywords: ['add expense', 'rent', 'salary', 'electricity', 'bill', 'spend', 'cost'],
            steps: [
              'Open Finance and go to the Expenses tab.',
              'Click “Add Expense” and choose a Category (Rent & Lease, Utilities, Salaries & Wages, and more).',
              'Enter Title, Amount, Expense date, and Payment method, then save.',
              'Edit or delete expenses from the table.',
            ],
          },
          {
            id: 'fi-statement',
            title: 'See income, expenses, and profit',
            keywords: ['profit', 'loss', 'statement', 'income', 'report', 'monthly report', 'earnings'],
            steps: [
              'Finance → Overview shows Fee Income, Total Expenses, Net Profit, and charts for the selected dates.',
              'Finance → Statement lists every fee payment and expense together; filter by type or payment method.',
              'Use the date range picker at the top for month-end reviews.',
            ],
          },
        ],
      },
      {
        id: 'enquiries-growth',
        title: 'Enquiries & growth',
        icon: 'fa-bullhorn',
        summary: 'Enquiries, follow-ups, referrals, and promotion emails.',
        topics: [
          {
            id: 'eg-enquiry',
            title: 'Add and follow up an enquiry',
            keywords: ['enquiry', 'inquiry', 'lead', 'walk in', 'follow up', 'call back', 'prospect'],
            steps: [
              'Open Enquiries and click “Add Enquiry”. Enter Name and Phone (Email and Message are optional) and save.',
              'Click “Edit” to update the status: New, Contacted, Followed up 1–3, Converted, Declined, or Closed.',
              'For follow-ups you can add a follow-up date and note.',
              'Enquiries from your public library website appear here automatically as New.',
            ],
          },
          {
            id: 'eg-convert',
            title: 'Mark an enquiry as converted',
            keywords: ['convert enquiry', 'enquiry joined', 'lead converted'],
            steps: [
              'Click “Convert” on the enquiry and confirm “Mark as converted”.',
              'This updates the status only — add the person under Students → Add Student to enrol them.',
            ],
          },
          {
            id: 'eg-referral',
            title: 'Run a referral campaign',
            keywords: ['referral', 'refer a friend', 'reward', 'refer', 'campaign'],
            steps: [
              'Open Grow My Library, write your Offer in the referral campaign box, and click “Start campaign”.',
              'Copy the “Message for students” and share it. Each student’s ID is their referral code.',
              'Friends enter that ID on your website enquiry form, or staff enter it in “Referred by” when adding the student.',
              'When a referred friend joins, click “Mark reward given” once you have given the reward.',
            ],
          },
          {
            id: 'eg-promotion',
            title: 'Send a promotion email to students',
            keywords: ['promotion', 'offer email', 'send email', 'announcement', 'bulk email', 'marketing'],
            steps: [
              'Select a specific branch in the top bar, then open Promotion.',
              'Write the Subject, Message, and an optional link.',
              'Choose all students with email or selected students, then click “Send promotion email”.',
              'Promotion email must be switched on in Settings → Emails and email (SMTP) must be set up.',
            ],
          },
          {
            id: 'eg-grow',
            title: 'Grow My Library',
            keywords: ['growth', 'marketing', 'google maps', 'reviews', 'ads', 'seo', 'more students'],
            steps: [
              'Grow My Library shows your Growth Score and recommended next steps.',
              'Add your Google Maps link, reviews, Facebook, and Instagram under the visibility checklist and click “Save & refresh score”.',
              'Request a marketing package or individual service (Google Ads, local SEO, social media) and track it under “Your growth requests”.',
            ],
          },
        ],
      },
      {
        id: 'branches-settings',
        title: 'Branches & settings',
        icon: 'fa-gear',
        summary: 'Branches, staff logins, settings, website, and emails.',
        topics: [
          {
            id: 'bs-branch',
            title: 'Add a branch and its login',
            keywords: ['add branch', 'new branch', 'branch manager', 'staff login', 'create branch', 'staff account'],
            steps: [
              'Open Branch and click “Create Branch”.',
              'Enter Branch name, contact details, the branch Email (used to sign in), and a Password (or “Generate password”).',
              'Click “Create Branch” and share the email and password with the branch manager.',
            ],
          },
          {
            id: 'bs-branch-password',
            title: 'Reset a branch manager’s password',
            keywords: ['branch password', 'staff password', 'reset branch', 'manager forgot password'],
            steps: [
              'Open Branch, click View on the branch, then “Reset Password”.',
              'Type a password or click “Generate password”, then “Set Password”.',
              'Copy the new password shown — it is displayed only once.',
            ],
          },
          {
            id: 'bs-switch',
            title: 'Switch between branches',
            keywords: ['switch branch', 'change branch', 'all branches', 'multiple branches'],
            steps: [
              'Owners use the Branch dropdown in the top bar to pick a branch or “All branches”.',
              'Some actions (like library hours or promotion emails) need a specific branch selected.',
              'Branch managers always work in their own branch.',
            ],
          },
          {
            id: 'bs-settings',
            title: 'Library settings',
            keywords: ['settings', 'student id prefix', 'student code', 'library code', 'reminder days', 'configuration'],
            steps: [
              'Open the account menu (top right) → Branch Settings.',
              'General: library code, student ID letters and digits, library hours, and plan expiry reminder days.',
              'ID Cards: library logo and card template. Website: your public library website. Emails: welcome, birthday, promotion, and recovery emails.',
              'Click the Save button on each tab.',
            ],
          },
          {
            id: 'bs-website',
            title: 'Set up your library website',
            keywords: ['website', 'public site', 'library website', 'gallery', 'online page'],
            steps: [
              'Go to Settings → Website and turn on “Enable public website”.',
              'Add library name, tagline, amenities, social links, WhatsApp number, logo, and gallery photos.',
              'Click “Save website”. Enquiries from the website arrive in Enquiries.',
            ],
          },
          {
            id: 'bs-emails',
            title: 'Student email notifications',
            keywords: ['email', 'smtp', 'welcome email', 'birthday email', 'reminder email', 'fee reminder', 'expiry reminder', 'emails not sending', 'email not received'],
            steps: [
              'Settings → Emails has switches for Welcome, Birthday, Promotion, and Recovery emails.',
              'Plan expiry reminders are sent the number of days before expiry set in Settings → General.',
              'Emails go only to students with an email address and need email (SMTP) to be configured — ask Phenomit support if unsure.',
            ],
          },
          {
            id: 'bs-notifications',
            title: 'Notifications and activity log',
            keywords: ['notification', 'bell', 'alerts', 'activity log', 'who changed', 'history', 'audit'],
            steps: [
              'The bell icon shows new enquiries and plans ending soon; click “View all” for the full list.',
              'Owners can open Activity Log to see who did what and when, with the exact changes.',
            ],
          },
        ],
      },
      {
        id: 'attendance',
        title: 'Attendance (add-on)',
        icon: 'fa-calendar-check',
        summary: 'QR check-in and daily attendance register.',
        topics: [
          {
            id: 'at-register',
            title: 'Daily attendance register',
            keywords: ['attendance', 'present', 'absent', 'late', 'check in', 'check out', 'register'],
            steps: [
              'Open Attendance to see today’s Present, Absent, Late, and Not Marked counts.',
              'Filter by status, hall, or check-in method, or click “Mark Present” for a student.',
              'Open a student’s Attendance Report for their calendar history.',
            ],
          },
          {
            id: 'at-qr',
            title: 'Set up QR check-in',
            keywords: ['qr attendance', 'qr poster', 'geofence', 'gps', 'mobile check in'],
            steps: [
              'Attendance → Setup: turn on “Student QR (mobile web)” and set your location and radius.',
              'Click “Save settings”, then “Open QR image” and print the branch QR poster.',
              'Students scan it, enter their student code and phone number, and check in or out.',
            ],
          },
        ],
      },
      {
        id: 'settings-support',
        title: 'Help & support',
        icon: 'fa-life-ring',
        summary: 'Support tickets and contacting Phenomit.',
        topics: [
          {
            id: 'ss-ticket',
            title: 'Create a support ticket',
            keywords: ['support ticket', 'raise ticket', 'complaint', 'bug', 'problem', 'issue', 'not working', 'help'],
            steps: [
              'Open the account menu (top right) → Help & Support.',
              'Under “Create a support ticket”, choose Category and Priority, add a Subject and Message.',
              'Attach up to 5 screenshots or files, then click “Submit Ticket”.',
              'Track the status under “Your recent tickets”.',
            ],
          },
          {
            id: 'ss-contact',
            title: 'Contact Phenomit support',
            keywords: ['contact', 'whatsapp', 'phone number', 'email support', 'support hours', 'call'],
            steps: [
              'Email info@phenomit.com or WhatsApp / call +91 8076 105 181.',
              'Support hours: Monday to Saturday, 9 AM – 6 PM IST.',
              'Include your library name and a screenshot so we can help faster.',
            ],
          },
        ],
      },
    ],
  };
})(window);
