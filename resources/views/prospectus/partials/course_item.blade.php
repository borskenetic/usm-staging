<li id="course-{{ $course->id }}"
    class="prospectus-course-item"
    data-course-id="{{ $course->id }}"
    data-year-id="{{ $course->program_year_id }}">
    <div class="prospectus-course-item__text">
        <strong>{{ $course->course_code }}</strong>
        <span>{{ $course->course_name }}</span>
    </div>
    <div class="prospectus-course-item__actions">
        <button type="button"
                class="prospectus-btn prospectus-btn--outline prospectus-btn--xs"
                onclick="openEditModal({{ $course->id }}, @js($course->course_code), @js($course->course_name))">
            Edit
        </button>
        <button type="button"
                class="prospectus-btn prospectus-btn--danger prospectus-btn--xs"
                onclick="openDeleteModal({{ $course->id }}, @js($course->course_code))">
            Delete
        </button>
    </div>
</li>
