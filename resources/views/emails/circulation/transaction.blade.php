@php
    $kind = $kind ?? ($renewal ?? false ? 'Renewal' : $log->circulationLabel());
    $patron = $log->patronLabel();
    $book = $log->book;
    $title = $book?->title_statement ?: 'Library book';
    $when = $log->timestamp?->timezone('Asia/Manila')->format('M j, Y g:i A');
    $due = $log->due_date?->timezone('Asia/Manila')->format('M j, Y');
    $returned = $log->returned_date?->timezone('Asia/Manila')->format('M j, Y g:i A');
    $fine = $log->fine_incurred !== null ? (float) $log->fine_incurred : 0;
    $appName = config('app.name', 'PANTAS');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $kind }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#333333;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f6f8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#1b5e20;padding:28px 32px;">
                            <p style="margin:0 0 6px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.85);">{{ $appName }} Library</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;color:#ffffff;">{{ $kind }} recorded</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Hello {{ $patron }},</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">
                                A library transaction was recorded on your account. The details are below.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;width:38%;font-size:14px;color:#64748b;">Transaction</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;font-weight:bold;">{{ $kind }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Book</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $title }}</td>
                                </tr>
                                @if($book?->main_author)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Author</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $book->main_author }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Copy</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $book?->copyIdentifierSummary() ?? '—' }}</td>
                                </tr>
                                @if($when)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">When</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $when }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Due</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $due ?? 'No due date' }}</td>
                                </tr>
                                @if($returned)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Returned</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ $returned }}</td>
                                </tr>
                                @endif
                                @if($renewal)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Renewals used</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">{{ (int) $log->renew_count }}</td>
                                </tr>
                                @endif
                                @if($fine > 0)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;color:#64748b;">Fine</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;font-size:14px;">₱{{ number_format($fine, 2) }}</td>
                                </tr>
                                @endif
                            </table>
                            <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                @if($kind === 'Room use')
                                    This copy stays in the library. Please return it to the desk when you are finished.
                                @elseif($kind === 'Check in')
                                    This copy has been checked in.
                                @elseif($due)
                                    Please return the book by the due date.
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
