<x-admin-field name="name" label="Subject name" :value="$record->name" maxlength="100" required/>

<x-admin-field name="price" label="Price (RM)" type="number" :value="$record->price" min="0" max="99999999.99" step="0.01" required/>

<x-admin-field name="is_active" label="Active" type="checkbox" :value="$record->is_active"/>
