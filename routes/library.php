<?php

use App\Http\Controllers\ActiveCheckoutController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookImportController;
use App\Http\Controllers\BookLogController;
use App\Http\Controllers\BookReservationAdminController;
use App\Http\Controllers\BorrowRequestAdminController;
use App\Http\Controllers\CatalogFrameworkAdminController;
use App\Http\Controllers\CatalogMarcSelectOptionsController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeIdCardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FineClearanceController;
use App\Http\Controllers\FineSettingController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\LibraryAttendanceController;
use App\Http\Controllers\OpenLibraryCopyCatalogController;
use App\Http\Controllers\PendingEmployeeController;
use App\Http\Controllers\PendingStudentController;
use App\Http\Controllers\ProspectusController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\RFIDScanController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomReservationController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/index', fn () => redirect()->route('book.index'));
Route::get('/filter/years', [BookController::class, 'getYears']);
Route::get('/filter/courses', [BookController::class, 'getCourses']);

Route::get('/register', [PublicRegistrationController::class, 'choose'])->name('patron.register');
Route::post('/register', [PendingStudentController::class, 'store'])->name('pending.store');
Route::post('/register-employee', [PendingEmployeeController::class, 'store'])->name('pendingEmployee.store');
Route::redirect('/patrons/register', '/register');
Route::get('/register/library', [PendingStudentController::class, 'create'])->name('library.register');
Route::post('/register/library', [PendingStudentController::class, 'store'])->name('library.pending.store');
Route::post('/register/library/employee', [PendingEmployeeController::class, 'store'])->name('library.pendingEmployee.store');

Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
Route::get('/books/copies', [BookController::class, 'viewCopies'])->name('books.copies');
Route::get('/opac', [BookController::class, 'landingPage'])->name('landing');
Route::get('/opac/api/book/{book}', [BookController::class, 'opacBookDetails'])->name('opac.book.details');
Route::get('/kiosk/scan', fn () => view('kiosk.scan'))->name('kiosk.scan');
Route::get('/library/attendance/scanner', [LibraryAttendanceController::class, 'scanner'])->name('library.attendance.scanner');
Route::post('/library/attendance/scanner', [LibraryAttendanceController::class, 'scan'])->name('library.attendance.scan');
Route::post('/library/attendance/feedback', [LibraryAttendanceController::class, 'feedback'])->name('library.attendance.feedback.store');
Route::get('/student/qr/{qrcode}', [StudentController::class, 'profile'])->name('student.qr.profile');
Route::post('/students/profile/request', [StudentController::class, 'submitEditRequest'])->name('students.profile.request');

Route::get('/rooms/book', [RoomReservationController::class, 'create'])->name('rooms.book');
Route::post('/rooms/book', [RoomReservationController::class, 'store'])->name('room-reservations.store');
Route::get('/rooms/schedule', [RoomReservationController::class, 'schedule'])->name('rooms.schedule');
Route::get('/rooms/{id}/show', [RoomReservationController::class, 'show'])->name('rooms.show');

Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
Route::post('/checkout/bulk', [CheckoutController::class, 'bulk'])->name('checkout.bulk');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard/library-admin', [DashboardController::class, 'libraryAdmin'])
        ->middleware('library.admin')
        ->name('dashboard.library-admin');

    Route::get('/dashboard/library-staff', [DashboardController::class, 'libraryStaff'])
        ->middleware('library.access')
        ->name('dashboard.library-staff');
});

Route::middleware(['auth', 'library.access'])->group(function (): void {
    Route::resource('book', BookController::class);
    Route::get('/book/catalog/courses-for-programs', [BookController::class, 'coursesForPrograms'])->name('books.coursesForPrograms');
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/staff/books/copies', [BookController::class, 'viewCopiesStaff'])->name('books.copies.staff');
    Route::get('/staff/books/archived', [BookController::class, 'archivedIndex'])->name('books.archived');
    Route::get('/staff/books/trash', [BookController::class, 'trashIndex'])->name('books.trash');
    Route::post('/books/{book}/archive', [BookController::class, 'archive'])->name('books.archive');
    Route::post('/books/{book}/unarchive', [BookController::class, 'unarchive'])->name('books.unarchive');
    Route::post('/books/{id}/restore', [BookController::class, 'restoreTrashed'])->name('books.restore');
    Route::delete('/books/{id}/force-delete', [BookController::class, 'forceDeleteTrashed'])->name('books.forceDelete');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::post('/import-books', [BookImportController::class, 'import'])->name('books.import');

    Route::get('/rfid-scanner', [RFIDScanController::class, 'index'])->name('rfid.scanner');
    Route::post('/rfid-scan', [RFIDScanController::class, 'scan'])->name('rfid.scan');
    Route::get('/library/attendance/logout-feedback', [LibraryAttendanceController::class, 'feedbackSettings'])->name('library.attendance.feedback.settings');
    Route::post('/library/attendance/logout-feedback', [LibraryAttendanceController::class, 'updateFeedbackSettings'])->name('library.attendance.feedback.settings.update');

    Route::resource('ebooks', EbookController::class);
    Route::get('/program/{program?}/courses', [EbookController::class, 'getCourses'])->name('program.courses');
    Route::get('/ebooks/get-courses/{programId}', [EbookController::class, 'getCourses']);
    Route::get('/export-books', [ExportController::class, 'exportBooks'])->name('export.books');
    Route::get('/export-transactions', [ExportController::class, 'exportTransactions'])->name('transactions.export');
    Route::get('/download-book-report', [BookController::class, 'downloadBookReport'])->name('book.report.download');
    Route::get('/book-report-by-course', [BookController::class, 'bookReportByCourse'])->name('book.report.by.course');

    Route::get('/patron-suggestions', [BookLogController::class, 'patronSuggestions'])->name('patron.suggestions');
    Route::get('/book-suggestions', [BookLogController::class, 'bookSuggestions'])->name('book.suggestions');
    Route::get('/book-title-log-suggestions', [BookLogController::class, 'bookTitleLogSuggestions'])->name('book.title.log.suggestions');

    Route::get('/catalog/copy/openlibrary', [OpenLibraryCopyCatalogController::class, 'searchForm'])->name('catalog.copy.openlibrary.form');
    Route::match(['get', 'post'], '/catalog/copy/openlibrary/search', [OpenLibraryCopyCatalogController::class, 'search'])->name('catalog.copy.openlibrary.search');
    Route::post('/catalog/copy/openlibrary/store', [OpenLibraryCopyCatalogController::class, 'store'])->name('catalog.copy.openlibrary.store');

    Route::get('/feedbacks', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedbacks/{feedback}/read', [FeedbackController::class, 'markRead'])->name('feedback.read');
    Route::post('/feedbacks/read-all', [FeedbackController::class, 'markAllRead'])->name('feedback.read-all');
    Route::get('/holidays/list', [HolidayController::class, 'list'])->name('holidays.list');
    Route::post('/holidays/toggle', [HolidayController::class, 'toggle'])->name('holidays.toggle');
    Route::get('/holidays/all', [HolidayController::class, 'all'])->name('holidays.all');
    Route::post('/sms/send', [SMSController::class, 'send'])->name('sms.send');
});

Route::middleware(['auth', 'library.admin'])->group(function (): void {
    Route::get('/logs', [BookLogController::class, 'index'])->name('logs.index');
    Route::post('/logs', [BookLogController::class, 'store'])->name('logs.store');
    Route::post('/logs/{book}/renew', [BookLogController::class, 'renew'])->name('logs.renew');
    Route::get('/checkouts/active', [ActiveCheckoutController::class, 'index'])->name('checkouts.active');
    Route::post('/checkouts/{id}/confirm-return', [ActiveCheckoutController::class, 'confirmReturn'])->name('checkouts.confirm_return');
    Route::get('/library/attendance/logs', [LibraryAttendanceController::class, 'logs'])->name('library.attendance.logs');
    Route::get('/library/attendance/logs/reports', [LibraryAttendanceController::class, 'reports'])->name('library.attendance.reports');

    Route::prefix('prospectus')->name('prospectus.')->group(function (): void {
        Route::get('/', [ProspectusController::class, 'index'])->name('index');
        Route::post('/store-program', [ProspectusController::class, 'storeProgram'])->name('storeProgram');
        Route::get('/{program}/years', [ProspectusController::class, 'getProgramYears'])->name('getProgramYears');
    });
    Route::post('/prospectus/{year}/course', [ProspectusController::class, 'storeCourse'])->name('prospectus.storeCourse');
    Route::put('/prospectus/course/{course}', [ProspectusController::class, 'updateCourse'])->name('prospectus.updateCourse');
    Route::delete('/prospectus/course/{course}', [ProspectusController::class, 'destroyCourse'])->name('prospectus.destroyCourse');
    Route::put('/prospectus/program/{program}', [ProspectusController::class, 'updateProgram'])->name('prospectus.updateProgram');
    Route::delete('/prospectus/program/{program}', [ProspectusController::class, 'destroyProgram'])->name('prospectus.destroyProgram');
    Route::get('/prospectus/add-subject', [ProspectusController::class, 'createSubject'])->name('prospectus.addSubject');
    Route::post('/prospectus/store-subject', [ProspectusController::class, 'storeSubject'])->name('prospectus.storeSubject');

    Route::get('/students/report', [StudentController::class, 'index'])->name('students.report');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
    Route::resource('students', StudentController::class);
    Route::get('/idcard/download/{id}', [IdCardController::class, 'download'])->name('idcard.download');
    Route::get('/student/pending-requests', [StudentController::class, 'pendingRequests'])->name('students.pending.requests');
    Route::post('/admin/requests/{id}/approve', [StudentController::class, 'approveRequest'])->name('admin.requests.approve');
    Route::post('/admin/requests/{id}/reject', [StudentController::class, 'rejectRequest'])->name('admin.requests.reject');

    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::post('/files/upload', [FileController::class, 'upload'])->name('files.upload');
    Route::get('/files/view/{id}', [FileController::class, 'view'])->name('files.view');
    Route::get('/files/download/{id}', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/delete/{id}', [FileController::class, 'delete'])->name('files.delete');

    Route::get('/idcard/{id}', [IdCardController::class, 'generate']);
    Route::get('/idcard/front/{id}', [IdCardController::class, 'front']);
    Route::get('/idcard/back/{id}', [IdCardController::class, 'back'])->name('idcard.back');

    Route::get('/admin/pending', [PendingStudentController::class, 'index'])->name('students.pending');
    Route::post('/admin/pending/{id}/approve', [StudentController::class, 'approve'])->name('students.approve');
    Route::post('/admin/pending/{id}/reject', [StudentController::class, 'reject'])->name('students.reject');
    Route::get('/pending', [PendingStudentController::class, 'index'])->name('pending.index');
    Route::get('/pending/employees', [PendingEmployeeController::class, 'index'])->name('pending.employees');
    Route::post('/pending/employees/approve/{id}', [PendingEmployeeController::class, 'approve'])->name('employees.approve');
    Route::post('/pending/employees/reject/{id}', [PendingEmployeeController::class, 'reject'])->name('employees.reject');

    Route::prefix('employees')->group(function (): void {
        Route::get('/', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/edit/{id}', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/update/{id}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/delete/{id}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });
    Route::prefix('employees/idcard')->group(function (): void {
        Route::get('/front/{id}', [EmployeeIdCardController::class, 'front'])->name('employees.id.front');
        Route::get('/back/{id}', [EmployeeIdCardController::class, 'back'])->name('employees.id.back');
        Route::get('/download/{id}', [EmployeeIdCardController::class, 'download'])->name('employees.id.download');
    });

    Route::get('/rooms/pending', [RoomReservationController::class, 'pending'])->name('rooms.pending');
    Route::get('/books/reservations/pending', [BookReservationAdminController::class, 'pending'])->name('books.reservations.pending');
    Route::post('/books/reservations/{id}/fulfill', [BookReservationAdminController::class, 'fulfill'])->name('books.reservations.fulfill');
    Route::post('/books/reservations/{id}/cancel', [BookReservationAdminController::class, 'cancel'])->name('books.reservations.cancel');
    Route::get('/checkout/requests/pending', [BorrowRequestAdminController::class, 'pending'])->name('checkout.requests.pending');
    Route::post('/checkout/requests/{id}/approve', [BorrowRequestAdminController::class, 'approve'])->name('checkout.requests.approve');
    Route::post('/checkout/requests/{id}/reject', [BorrowRequestAdminController::class, 'reject'])->name('checkout.requests.reject');
    Route::post('/checkout/requests/items/{item}/approve', [BorrowRequestAdminController::class, 'approveItem'])->name('checkout.requests.items.approve');
    Route::post('/checkout/requests/items/{item}/reject', [BorrowRequestAdminController::class, 'rejectItem'])->name('checkout.requests.items.reject');
    Route::post('/rooms/{id}/approve', [RoomReservationController::class, 'approve'])->name('rooms.approve');
    Route::post('/rooms/reject/{id}', [RoomReservationController::class, 'reject'])->name('rooms.reject');
    Route::delete('/resrooms/{id}', [RoomReservationController::class, 'destroy'])->name('resrooms.destroy');
    Route::get('/rooms/check-availability', [RoomReservationController::class, 'checkAvailability'])->name('rooms.check');
    Route::get('/rooms/logs', [RoomReservationController::class, 'logs'])->name('rooms.logs');
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{id}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
    Route::put('/rooms/{id}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    Route::get('/admin/fines', [FineSettingController::class, 'edit'])->name('fines.edit');
    Route::post('/admin/fines', [FineSettingController::class, 'update'])->name('fines.update');
    Route::get('/admin/fines/outstanding', [FineClearanceController::class, 'index'])->name('fines.outstanding');
    Route::post('/admin/fines/logs/{bookLog}/clear', [FineClearanceController::class, 'clear'])->name('fines.logs.clear');

    Route::get('/sms-blast', [SMSController::class, 'index'])->name('sms.page');
    Route::get('/sms/scan-message', [SMSController::class, 'scanMessage'])->name('sms.scan-message');
    Route::post('/sms/scan-message', [SMSController::class, 'updateScanMessage'])->name('sms.scan-message.update');
    Route::post('/sms/send-one-student', [SMSController::class, 'sendOneStudent'])->name('sms.send-one-student');
    Route::post('/sms/send-overdue', [SMSController::class, 'sendOverdue'])->name('sms.send-overdue');
    Route::get('/sms/count', [SMSController::class, 'count'])->name('sms.count');

    Route::prefix('admin/catalog-frameworks')->name('admin.catalog_frameworks.')->group(function (): void {
        Route::get('/', [CatalogFrameworkAdminController::class, 'index'])->name('index');
        Route::get('/{catalog_framework}/edit', [CatalogFrameworkAdminController::class, 'edit'])->name('edit');
        Route::put('/{catalog_framework}/fields', [CatalogFrameworkAdminController::class, 'updateFields'])->name('fields.update');
        Route::post('/{catalog_framework}/fields', [CatalogFrameworkAdminController::class, 'attachField'])->name('fields.attach');
        Route::post('/{catalog_framework}/marc-fields', [CatalogFrameworkAdminController::class, 'storeMarcField'])->name('marc_fields.store');
        Route::delete('/{catalog_framework}/fields/{field}', [CatalogFrameworkAdminController::class, 'detachField'])->name('fields.detach');
    });
    Route::get('/admin/catalog-select-options', [CatalogMarcSelectOptionsController::class, 'index'])->name('admin.catalog_select_options.index');
    Route::post('/admin/catalog-select-options', [CatalogMarcSelectOptionsController::class, 'store'])->name('admin.catalog_select_options.store');
    Route::delete('/admin/catalog-select-options', [CatalogMarcSelectOptionsController::class, 'destroy'])->name('admin.catalog_select_options.destroy');
});
