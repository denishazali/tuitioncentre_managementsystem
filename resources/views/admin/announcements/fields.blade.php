<x-admin-field
    name="title"
    label="Title"
    :value="$record->title"
/>

<x-admin-field
    name="content"
    label="Content"
    type="textarea"
    :value="$record->content"
    required
/>

<x-admin-field
    name="image_path"
    label="Image path inside public/uploads"
    :value="$record->image_path"
    placeholder="announcements/new-term.jpg"
/>

<x-admin-field
    name="published_at"
    label="Publication date and time"
    type="datetime-local"
    :value="$record->published_at?->format('Y-m-d\TH:i')"
/>

<p class="text-sm text-gray-600">
    Leave the publication date empty to save a draft.
    A future date schedules publication.
    Times use {{ config('app.timezone') }}.
</p>
