<x-admin-field name="name" label="Package name" :value="$record->name" maxlength="100" required/>
<x-admin-field name="price" label="Fixed package price (RM)" type="number" :value="$record->price" min="0" max="99999999.99" step="0.01" required/>

<p class="text-sm text-gray-600">
    Parent pay this fixed price. Individual subject prices are not added.
</p>

<x-admin-field name="elective_subject_limit" label="Maximum elective subjects per enrollment" type="number" :value="$record->elective_subject_limit" min="0" max="255" step="1" required/>

<p class="text-sm text-gray-600">
    Use zero for a package containing compulsory subjects only.
    The limit cannot exceed the number of elective selected below.
</p>

<x-admin-field name="compulsory_ids" label="Compulsory subjects" type="select" :options="$subjectOptions" :value="$compulsoryIds" multiple/>

<x-admin-field name="elective_ids" label="Elective subjects" type="select" :options="$subjectOptions" :value="electiveIds" multiple/>

<p class="text-sm text-gray-600">
    Hold Ctrl on windows or Command on Mac to select multiple subjects.
    A subject can belong to only one group
</p>

<x-admin-field name="is_active" label="active" type="checkbox" :value="$record->is_active"/>

<p class="text-sm text-gray-600">
    Deactivate a package to stop offering it for new enrollments.
    Packages with enrollment history cannot be deleted.
</p>