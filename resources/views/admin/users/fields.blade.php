<x-admin-field
    name="name"
    label="Name"
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
    name="role"
    label="Role"
    type="select"
    :value="$record->role"
    :options="['admin' => 'Administrator', 'parent' => 'Parent', 'student' => 'Student',]"
    required
/>

<x-admin-field 
    name="account_status"
    label="Account status"
    type="select"
    :value="$record->account_status"
    :options="['pending' => 'Pending approval', 'active' => 'Active', 'inactive' => 'Inactive',]"
    required
/>

<x-admin-field 
    name="password"
    label="Password"
    type="password"
    autocomplete="new-password"
    minlength="8"
    maxlength="72"
/>

<x-admin-field
    name="password_confirmation"
    label="Confirm password"
    type="password"
    autocomplete="new-password"
/>

@if ($record->exists)
    <p class="text-sm text-gray-600">
        Leave both password fields empty to keep the existing password.
    </p>
@endif

<p class="text-sm text-gray-600">
    This form manages login accounts.
    Selecting Student does not create a student profile.
</p>
