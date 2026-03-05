<div class="form-group">
    <label for="project_id" class="form-label">Project</label>
    <select name="project_id" id="project_id" class="form-input">
        <option value="">— No project —</option>
        @foreach($projects as $project)
        <option value="{{ $project->id }}" {{ old('project_id', $task->project_id ?? '') == $project->id ? 'selected' : '' }}>
            {{ $project->name }}
        </option>
        @endforeach
    </select>
    @error('project_id') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label for="name" class="form-label">Name *</label>
    <input
        type="text"
        name="name"
        id="name"
        class="form-input"
        value="{{ old('name', $task->name ?? '') }}"
        required>
    @error('name') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label for="description" class="form-label">Description</label>
    <textarea
        name="description"
        id="description"
        class="form-input"
        rows="2">{{ old('description', $task->description ?? '') }}</textarea>
    @error('description') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div
    x-data="{ type: '{{ old('command_type', $task->command_type ?? 'shell') }}' }"
    class="form-group">
    <label class="form-label">Command Type *</label>
    <div class="flex gap-3 mb-3">
        @foreach(['shell' => 'Shell Command', 'claude' => 'Claude CLI', 'copilot' => 'Copilot CLI'] as $value => $label)
        <label class="flex items-center gap-2 cursor-pointer">
            <input
                type="radio"
                name="command_type"
                value="{{ $value }}"
                x-model="type"
                {{ old('command_type', $task->command_type ?? 'shell') === $value ? 'checked' : '' }}>
            <span class="text-sm font-medium">{{ $label }}</span>
        </label>
        @endforeach
    </div>

    <p x-show="type === 'shell'" class="text-sm text-stone-500 mb-2">
        Any shell command — e.g. <code class="font-mono text-xs">git pull origin main</code>
    </p>
    <p x-show="type === 'claude'" class="text-sm text-blue-600 mb-2">
        Claude CLI — e.g. <code class="font-mono text-xs">claude --dangerously-skip-permissions -p "summarize recent changes"</code>
    </p>
    <p x-show="type === 'copilot'" class="text-sm text-purple-600 mb-2">
        GitHub Copilot CLI — e.g. <code class="font-mono text-xs">gh copilot suggest "explain this error"</code>
    </p>

    <label for="command" class="form-label">Command *</label>
    <textarea
        name="command"
        id="command"
        class="form-input font-mono text-sm"
        rows="3"
        :placeholder="type === 'claude' ? 'claude --dangerously-skip-permissions -p \" ...\"' : type==='copilot' ? 'gh copilot suggest \"...\"' : 'echo hello world'"
        required
    >{{ old('command', $task->command ?? '') }}</textarea>
    @error('command') <p class=" form-error">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label for="working_directory" class="form-label">Working Directory</label>
    <input
        type="text"
        name="working_directory"
        id="working_directory"
        class="form-input font-mono text-sm"
        placeholder="/Users/paul/my-project"
        value="{{ old('working_directory', $task->working_directory ?? '') }}">
    <p class="text-xs text-stone-400 mt-1">Absolute path. Leave blank to use the app root.</p>
    @error('working_directory') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Environment Variables --}}
<div
    x-data="envVarEditor({{ json_encode(old('env_vars', $task->env_vars ?? [])) }})"
    class="form-group">
    <label class="form-label">Environment Variables</label>
    <div class="space-y-2">
        <template x-for="(pair, index) in pairs" :key="index">
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    :name="`env_vars[${index}][key]`"
                    x-model="pair.key"
                    placeholder="KEY"
                    class="form-input font-mono text-sm w-40 uppercase"
                    autocomplete="off">
                <span class="text-stone-400">=</span>
                <input
                    type="text"
                    :name="`env_vars[${index}][value]`"
                    x-model="pair.value"
                    placeholder="value"
                    class="form-input font-mono text-sm flex-1">
                <button type="button" @click="removePair(index)" class="text-stone-400 hover:text-red-500 text-sm px-1">✕</button>
            </div>
        </template>
    </div>
    <button type="button" @click="addPair()" class="btn-secondary mt-2 text-xs">+ Add variable</button>
    <p class="text-xs text-stone-400 mt-1">Prepended to the command: <code class="font-mono">KEY=value command…</code></p>
    @error('env_vars.*.key') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label class="form-label">Schedule *</label>
    @include('tasks._schedule-builder', ['expression' => old('cron_expression', $task->cron_expression ?? '* * * * *')])
    @error('cron_expression') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Tags --}}
<div
    x-data="tagEditor({{ json_encode(old('tags', $task->tags?->pluck('name')->all() ?? [])) }}, {{ json_encode($allTags->pluck('name')->all()) }})"
    class="form-group">
    <label class="form-label">Tags</label>

    {{-- existing tags as hidden inputs + removable chips --}}
    <div class="flex flex-wrap gap-2 mb-2">
        <template x-for="(tag, i) in selected" :key="i">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 border border-indigo-200">
                <input type="hidden" :name="`tags[${i}]`" :value="tag">
                <span x-text="tag"></span>
                <button type="button" @click="remove(i)" class="hover:text-red-500 leading-none">✕</button>
            </span>
        </template>
    </div>

    {{-- existing suggestions + free-text input --}}
    <div class="flex items-center gap-2">
        <input
            type="text"
            x-model="input"
            @keydown.enter.prevent="add()"
            @keydown.comma.prevent="add()"
            placeholder="Add tag…"
            class="form-input py-1.5 text-sm flex-1"
            list="tag-suggestions">
        <datalist id="tag-suggestions">
            <template x-for="t in suggestions" :key="t">
                <option :value="t"></option>
            </template>
        </datalist>
        <button type="button" @click="add()" class="btn-secondary py-1.5 text-sm">Add</button>
    </div>
    <p class="text-xs text-stone-400 mt-1">Press Enter or comma to add. New tags are created automatically.</p>
    @error('tags.*') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Depends on --}}
<div class="form-group">
    <label for="depends_on_task_id" class="form-label">Depends on Task</label>
    <select name="depends_on_task_id" id="depends_on_task_id" class="form-input">
        <option value="">— None —</option>
        @foreach($allTasks as $otherTask)
        <option value="{{ $otherTask->id }}" {{ old('depends_on_task_id', $task->depends_on_task_id ?? '') == $otherTask->id ? 'selected' : '' }}>
            {{ $otherTask->name }}
        </option>
        @endforeach
    </select>
    <p class="text-xs text-stone-400 mt-1">This task will only run (via scheduler) if the selected task's most recent run was successful.</p>
    @error('depends_on_task_id') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Pause Until / Snooze --}}
<div class="form-group">
    <label for="paused_until" class="form-label">Pause Until</label>
    <input
        type="datetime-local"
        name="paused_until"
        id="paused_until"
        class="form-input w-56"
        value="{{ old('paused_until', $task->paused_until ? $task->paused_until->format('Y-m-d\TH:i') : '') }}">
    <p class="text-xs text-stone-400 mt-1">Scheduler skips this task until the specified time. Leave blank to not snooze.</p>
    @error('paused_until') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="form-group flex items-center gap-3">
    <input type="hidden" name="is_enabled" value="0">
    <input
        type="checkbox"
        name="is_enabled"
        id="is_enabled"
        value="1"
        {{ old('is_enabled', $task->is_enabled ?? true) ? 'checked' : '' }}
        class="rounded border-stone-300 text-amber-500 focus:ring-amber-400">
    <label for="is_enabled" class="form-label mb-0">Enable this task</label>
</div>

<div class="form-group flex items-center gap-3">
    <input type="hidden" name="prevent_overlap" value="0">
    <input
        type="checkbox"
        name="prevent_overlap"
        id="prevent_overlap"
        value="1"
        {{ old('prevent_overlap', $task->prevent_overlap ?? false) ? 'checked' : '' }}
        class="rounded border-stone-300 text-amber-500 focus:ring-amber-400">
    <label for="prevent_overlap" class="form-label mb-0">Prevent overlapping runs</label>
    <p class="text-xs text-stone-400">Skip if a previous run of this task is still in progress.</p>
</div>

<div class="form-group">
    <label for="runs_to_keep" class="form-label">Run history to keep</label>
    <input
        type="number"
        name="runs_to_keep"
        id="runs_to_keep"
        class="form-input w-32"
        min="1"
        max="10000"
        placeholder="Unlimited"
        value="{{ old('runs_to_keep', $task->runs_to_keep ?? '') }}">
    <p class="text-xs text-stone-400 mt-1">Maximum number of run history records to retain. Leave blank for unlimited.</p>
    @error('runs_to_keep') <p class="form-error">{{ $message }}</p> @enderror
</div>

{{-- Failure notifications --}}
<div
    x-data="{ notify: {{ old('notify_on_failure', ($task->notify_on_failure ?? false) ? 'true' : 'false') }} }"
    class="form-group">
    <div class="flex items-center gap-3 mb-3">
        <input type="hidden" name="notify_on_failure" value="0">
        <input
            type="checkbox"
            name="notify_on_failure"
            id="notify_on_failure"
            value="1"
            x-model="notify"
            {{ old('notify_on_failure', $task->notify_on_failure ?? false) ? 'checked' : '' }}
            class="rounded border-stone-300 text-amber-500 focus:ring-amber-400">
        <label for="notify_on_failure" class="form-label mb-0">Notify on failure</label>
    </div>

    <div x-show="notify" x-cloak class="space-y-3 pl-6 border-l-2 border-amber-200">
        <div>
            <label for="notification_email" class="form-label">Email address</label>
            <input
                type="email"
                name="notification_email"
                id="notification_email"
                class="form-input"
                placeholder="you@example.com"
                value="{{ old('notification_email', $task->notification_email ?? '') }}">
            @error('notification_email') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="notification_webhook" class="form-label">Webhook URL</label>
            <input
                type="url"
                name="notification_webhook"
                id="notification_webhook"
                class="form-input font-mono text-sm"
                placeholder="https://hooks.slack.com/…"
                value="{{ old('notification_webhook', $task->notification_webhook ?? '') }}">
            <p class="text-xs text-stone-400 mt-1">A POST request with <code class="font-mono text-xs">{ task, exit_code, output }</code> will be sent on failure.</p>
            @error('notification_webhook') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
