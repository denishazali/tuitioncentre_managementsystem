<x-admin-field
    name="day"
    label="Day"
    type="select"
    :value="$record->day"
    :options="$dayOptions"
    required
/>

<x-admin-field 
    name="start_time"
    label="Start time"
    type="time"
    :value="$record->start_time 
        ? substr($record->start_time, 0, 5)
        : null"
    required
/>

<x-admin-field 
    name="duration_minutes"
    label="Duration in minutes"
    type="number"
    :value="$record->duration_minutes"
    min="1"
    max="1440"
    required
/>

<x-admin-field
    name="subject_id"
    label="Subject"
    type="select"
    :value="$record->subject_id"
    :options="$subjectOptions"
    required
/>

<x-admin-field 
    name="teacher_id"
    label="Teacher"
    type="select"
    :value="$record->teacher_id"
    :options="$teacherOptions"
    required
/>

<x-admin-field
    name="form_level"
    label="Form level"
    type="select"
    :value="$record->form_level"
    :options="[
        1 => 'Form 1',
        2 => 'Form 2',
        3 => 'Form 3',
        4 => 'Form 4',
        5 => 'Form 5',
    ]"
    required
/>

<p class="text-sm text-gray-600">
    Choose a teacher whose listed subjects include your selected subject.
    Classes cannot overlap for the same teacher or form level.
</p>
