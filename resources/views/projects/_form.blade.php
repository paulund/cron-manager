<div class="form-group">
    <label for="name" class="form-label">Name *</label>
    <input
        type="text"
        name="name"
        id="name"
        class="form-input"
        value="{{ old('name', $project->name ?? '') }}"
        required
    >
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label for="description" class="form-label">Description</label>
    <textarea
        name="description"
        id="description"
        class="form-input"
        rows="2"
    >{{ old('description', $project->description ?? '') }}</textarea>
    @error('description') <p class="form-error">{{ $message }}</p> @enderror
</div>
