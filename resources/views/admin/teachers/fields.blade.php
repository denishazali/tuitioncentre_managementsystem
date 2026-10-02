<x-admin-field 
    name="name"
    label="Teacher name"
    :value="$record->name"
    required
/>

<x-admin-field 
    name="email"
    label="Email"
    type="email"
    :value="$record->email"
    required
/>

<x-admin-field 
    name="photo_path"
    label="Photo path inside public/uploads"
    :value="$record->photo_path"
    placeholder="teachers/teacher-name.jpg"
/>

<x-admin-field
    name="background"
    label="Background"
    type="textarea"
    :value="$record->background"
/>

<x-admin-field
    name="subjects"
    label="Subjects taught"
    type="select"
    :options="$subjectOptions"
    :value="$selectedSubjects"
    :multiple="true"
/>

<p class="text-sm text-gray-600">
    On Windows, hold Ctrl to select multiple subjects. Create subjects before assigning them to teachers.
</p>

<x-admin-field 
    name="is_active"
    label="Active"
    type="checkbox"
    :value="$record->is_active"
/>
