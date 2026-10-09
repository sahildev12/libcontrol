<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card · {{ $student->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/student-id-cards.css'])
    <style>
        @page { size: auto; margin: 12mm; }
        @media print {
            .id-card-page-chrome,
            .id-card-page-preview-meta { display: none !important; }
            .id-card-page-body { background: #fff !important; }
            .id-card-page-stage { box-shadow: none !important; border: 0 !important; background: transparent !important; padding: 0 !important; }
            .id-card-page-mat { background: transparent !important; padding: 0 !important; min-height: 0 !important; }
            .id-card-page-scale { transform: none !important; }
        }
    </style>
</head>
<body
    class="id-card-page-body"
    style="margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', Arial, Helvetica, sans-serif; background: linear-gradient(165deg, #e8edf8 0%, #dce4f5 45%, #cfd9ef 100%); color: #0f172a;"
>
    <header
        class="id-card-page-chrome"
        style="position: sticky; top: 0; z-index: 10; border-bottom: 1px solid rgb(36 58 139 / 0.12); background: rgb(255 255 255 / 0.92); backdrop-filter: blur(10px);"
    >
        <div style="max-width: 56rem; margin: 0 auto; padding: 0.875rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
            <a
                href="{{ route('students.index') }}"
                style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; font-weight: 600; color: #243a8b; text-decoration: none;"
            >
                <span aria-hidden="true">←</span> Back to students
            </a>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                @if ($canDownloadImage)
                    <a
                        href="{{ route('students.id-card.download', $student) }}"
                        style="display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 0.625rem; border: 1px solid #cbd5e1; background: #fff; color: #0c1648; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 700; text-decoration: none;"
                    >
                        Download PNG
                    </a>
                @endif
                <a
                    href="{{ route('students.id-card.print', $student) }}"
                    target="_blank"
                    rel="noopener"
                    style="display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 0.625rem; border: 1px solid rgb(36 58 139 / 0.25); background: #fff; color: #243a8b; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 700; text-decoration: none;"
                >
                    Print card only
                </a>
                <button
                    type="button"
                    onclick="window.print()"
                    style="display: inline-flex; align-items: center; gap: 0.5rem; border: 0; border-radius: 0.625rem; background: linear-gradient(135deg, #243a8b 0%, #1a2d6e 100%); color: #fff; padding: 0.625rem 1.125rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgb(36 58 139 / 0.35);"
                >
                    Print this page
                </button>
            </div>
        </div>
    </header>

    <main style="max-width: 56rem; margin: 0 auto; padding: 1.5rem 1.25rem 3rem;">
        <div class="id-card-page-chrome" style="margin-bottom: 1.5rem;">
            <p style="margin: 0 0 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                Student ID card
            </p>
            <h1 style="margin: 0 0 0.5rem; font-size: 1.75rem; font-weight: 700; color: #0c1648; line-height: 1.2;">
                {{ $student->name }}
            </h1>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <span style="display: inline-flex; align-items: center; border-radius: 9999px; background: #fff; padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #243a8b; box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);">
                    {{ $student->student_code }}
                </span>
                @if (! empty($branchName))
                    <span style="display: inline-flex; align-items: center; border-radius: 9999px; background: #fff; padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 500; color: #475569; box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);">
                        {{ $branchName }}
                    </span>
                @endif
                <span style="display: inline-flex; align-items: center; border-radius: 9999px; background: #fecf25; padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #0c1648;">
                    {{ $membershipPlan }}
                </span>
            </div>
        </div>

        <div style="display: grid; gap: 1.25rem; grid-template-columns: 1fr;">
            <section
                class="id-card-page-stage"
                style="border-radius: 1rem; border: 1px solid rgb(36 58 139 / 0.1); background: #fff; padding: 1.5rem 1.25rem 2rem; box-shadow: 0 10px 40px rgb(36 58 139 / 0.08);"
            >
                <div class="id-card-page-preview-meta" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
                    <div>
                        <h2 style="margin: 0; font-size: 1rem; font-weight: 700; color: #0c1648;">
                            Print preview
                        </h2>
                        <p style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: #64748b;">
                            This is how the card will look on paper. Only the card prints — not this page chrome.
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em;">
                        CR80 · 86 × 54 mm
                    </span>
                </div>

                <div
                    class="id-card-page-mat"
                    style="display: flex; justify-content: center; align-items: center; min-height: 14rem; padding: 1.5rem; border-radius: 0.75rem; background: repeating-linear-gradient(-45deg, #f1f5f9, #f1f5f9 8px, #e8edf3 8px, #e8edf3 16px);"
                >
                    <div class="id-card-page-scale" style="transform: scale(1.15); transform-origin: center center;">
                        @includeFirst(['students.id-cards.'.$template, 'students.id-cards.classic'])
                    </div>
                </div>
            </section>

            <aside
                class="id-card-page-chrome"
                style="border-radius: 1rem; border: 1px solid rgb(36 58 139 / 0.1); background: rgb(255 255 255 / 0.85); padding: 1.25rem 1.5rem;"
            >
                <h3 style="margin: 0 0 0.75rem; font-size: 0.9375rem; font-weight: 700; color: #0c1648;">
                    Before you print
                </h3>
                <ul style="margin: 0; padding-left: 1.125rem; font-size: 0.875rem; line-height: 1.6; color: #475569;">
                    @if ($canDownloadImage)
                        <li style="margin-bottom: 0.5rem;"><strong style="color: #0c1648;">Download PNG</strong> gives a ready-to-print image (650×408 px) with artwork, photo, and text baked in — best for WhatsApp or a print shop.</li>
                    @endif
                    <li style="margin-bottom: 0.5rem;"><strong style="color: #0c1648;">Print card only</strong> opens a CR80-sized sheet (86×54&nbsp;mm) with no browser header/footer — turn on <strong>Background graphics</strong> in the print dialog.</li>
                    <li style="margin-bottom: 0.5rem;">In the print dialog, disable <strong>Headers and footers</strong> so the URL and date are not shown.</li>
                    <li>Valid until <strong style="color: #0c1648;">{{ $validTill }}</strong> on the membership plan shown above.</li>
                </ul>
            </aside>
        </div>
    </main>
</body>
</html>
