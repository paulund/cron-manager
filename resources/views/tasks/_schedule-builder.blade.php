<div
    x-data="scheduleBuilder('{{ $expression }}')"
    class="space-y-4"
    x-init="fetchPreview()"
    <input type="hidden" name="cron_expression" :value="cronExpression">

    <div class="flex gap-3 items-center">
        <div class="flex gap-2">
            <button type="button" @click="setMode('preset')"
                :class="mode === 'preset' ? 'btn-primary' : 'btn-secondary'">
                Preset
            </button>
            <button type="button" @click="setMode('custom')"
                :class="mode === 'custom' ? 'btn-primary' : 'btn-secondary'">
                Custom
            </button>
        </div>
    </div>

    <div x-show="mode === 'preset'" class="space-y-3">
        <div class="form-group mb-0">
            <label class="form-label">Frequency</label>
            <select x-model="frequency" class="form-input">
                <option value="every_minute">Every minute</option>
                <option value="every_n_minutes">Every N minutes</option>
                <option value="hourly">Hourly</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
        </div>

        <div x-show="frequency === 'every_n_minutes'" class="flex items-center gap-2">
            <span class="text-sm text-stone-600">Every</span>
            <input type="number" x-model="intervalMinutes" min="1" max="59" class="form-input w-20">
            <span class="text-sm text-gray-600">minutes</span>
        </div>

        <div x-show="['hourly','daily','weekly','monthly'].includes(frequency)" class="flex items-center gap-2">
            <span class="text-sm text-stone-600">At</span>
            <select x-model="hour" class="form-input w-24" x-show="['daily','weekly','monthly'].includes(frequency)">
                @for($h = 0; $h < 24; $h++)
                    <option value="{{ $h }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
            </select>
            <span x-show="['daily','weekly','monthly'].includes(frequency)" class="text-sm text-stone-600">:</span>
            <select x-model="minute" class="form-input w-24">
                @foreach([0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55] as $m)
                <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="frequency === 'weekly'" class="flex flex-wrap gap-2">
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $index => $day)
            <button
                type="button"
                @click="toggleDay({{ $index }})"
                :class="isDaySelected({{ $index }}) ? 'btn-primary' : 'btn-secondary'"
                class="!px-3 !py-1 text-xs">
                {{ $day }}
            </button>
            @endforeach
        </div>

        <div x-show="frequency === 'monthly'" class="flex items-center gap-2">
            <span class="text-sm text-stone-600">On day</span>
            <select x-model="dayOfMonth" class="form-input w-20">
                @for($d = 1; $d <= 28; $d++)
                    <option value="{{ $d }}">{{ $d }}</option>
                    @endfor
            </select>
        </div>
    </div>

    <div x-show="mode === 'custom'">
        <input
            type="text"
            x-model="cronExpression"
            class="form-input font-mono"
            placeholder="* * * * *">
        <p class="text-xs text-stone-400 mt-1">Format: minute hour day-of-month month day-of-week</p>
    </div>

    <p class="text-sm text-stone-500">
        <span x-text="humanReadable"></span>
        &nbsp;(<code x-text="cronExpression" class="font-mono text-xs"></code>)
    </p>

    {{-- Next 5 run preview --}}
    <div x-show="nextRuns.length > 0 || previewError" class="text-xs text-stone-400 space-y-0.5">
        <p class="font-semibold text-stone-500 mb-1">Next 5 scheduled runs:</p>
        <template x-if="previewError">
            <p class="text-red-500" x-text="previewError"></p>
        </template>
        <template x-for="(run, i) in nextRuns" :key="i">
            <p x-text="run" class="font-mono"></p>
        </template>
    </div>
</div>
