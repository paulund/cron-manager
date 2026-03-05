export default (initialExpression = '* * * * *') => ({
    mode: 'preset',
    frequency: 'every_minute',
    intervalMinutes: '5',
    minute: '0',
    hour: '9',
    daysOfWeek: [],
    dayOfMonth: '1',
    cronExpression: initialExpression,
    nextRuns: [],
    previewError: '',
    previewTimer: null,

    init() {
        this.parseExpression(this.cronExpression);
        this.$watch('frequency', () => this.sync());
        this.$watch('intervalMinutes', () => this.sync());
        this.$watch('minute', () => this.sync());
        this.$watch('hour', () => this.sync());
        this.$watch('daysOfWeek', () => this.sync());
        this.$watch('dayOfMonth', () => this.sync());
        this.$watch('cronExpression', () => this.debouncedFetchPreview());
    },

    debouncedFetchPreview() {
        clearTimeout(this.previewTimer);
        this.previewTimer = setTimeout(() => this.fetchPreview(), 400);
    },

    async fetchPreview() {
        const expr = this.cronExpression.trim();
        if (!expr) return;

        try {
            const url = `/cron-preview?expression=${encodeURIComponent(expr)}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();

            if (data.error) {
                this.nextRuns = [];
                this.previewError = data.error;
            } else {
                this.nextRuns = data.runs;
                this.previewError = '';
            }
        } catch {
            this.nextRuns = [];
            this.previewError = 'Could not load preview.';
        }
    },

    get computedExpression() {
        const h = this.hour;
        const m = this.minute;

        switch (this.frequency) {
            case 'every_minute':
                return '* * * * *';
            case 'every_n_minutes':
                return `*/${this.intervalMinutes} * * * *`;
            case 'hourly':
                return `${m} * * * *`;
            case 'daily':
                return `${m} ${h} * * *`;
            case 'weekly': {
                const days = this.daysOfWeek.length > 0
                    ? [...this.daysOfWeek].sort().join(',')
                    : '0';
                return `${m} ${h} * * ${days}`;
            }
            case 'monthly':
                return `${m} ${h} ${this.dayOfMonth} * *`;
            default:
                return this.cronExpression;
        }
    },

    sync() {
        if (this.mode === 'preset') {
            this.cronExpression = this.computedExpression;
        }
    },

    toggleDay(day) {
        const index = this.daysOfWeek.indexOf(day);
        if (index === -1) {
            this.daysOfWeek.push(day);
        } else {
            this.daysOfWeek.splice(index, 1);
        }
    },

    isDaySelected(day) {
        return this.daysOfWeek.includes(day);
    },

    setMode(mode) {
        this.mode = mode;
        if (mode === 'preset') {
            this.cronExpression = this.computedExpression;
        }
    },

    parseExpression(expr) {
        if (!expr || expr === '* * * * *') {
            this.frequency = 'every_minute';
            return;
        }

        const parts = expr.trim().split(/\s+/);
        if (parts.length !== 5) {
            this.frequency = 'custom';
            this.mode = 'custom';
            return;
        }

        const [min, hr, dom, , dow] = parts;

        // Every N minutes: */N * * * *
        if (min.startsWith('*/') && hr === '*' && dom === '*') {
            this.frequency = 'every_n_minutes';
            this.intervalMinutes = min.slice(2);
            return;
        }

        // Hourly: M * * * *
        if (hr === '*' && dom === '*') {
            this.frequency = 'hourly';
            this.minute = min;
            return;
        }

        // Monthly: M H D * *
        if (dow === '*' && dom !== '*') {
            this.frequency = 'monthly';
            this.minute = min;
            this.hour = hr;
            this.dayOfMonth = dom;
            return;
        }

        // Weekly: M H * * DOW
        if (dow !== '*' && dom === '*') {
            this.frequency = 'weekly';
            this.minute = min;
            this.hour = hr;
            this.daysOfWeek = dow.split(',').map(Number);
            return;
        }

        // Daily: M H * * *
        if (dow === '*' && dom === '*') {
            this.frequency = 'daily';
            this.minute = min;
            this.hour = hr;
            return;
        }

        // Fall through to custom
        this.frequency = 'custom';
        this.mode = 'custom';
    },

    get humanReadable() {
        const pad = (n) => String(n).padStart(2, '0');
        const time = `${pad(this.hour)}:${pad(this.minute)}`;

        switch (this.frequency) {
            case 'every_minute':
                return 'Every minute';
            case 'every_n_minutes':
                return `Every ${this.intervalMinutes} minutes`;
            case 'hourly':
                return `Every hour at :${pad(this.minute)}`;
            case 'daily':
                return `Daily at ${time}`;
            case 'weekly': {
                const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const days = this.daysOfWeek.length > 0
                    ? [...this.daysOfWeek].sort().map((d) => dayNames[d]).join(', ')
                    : 'Sun';
                return `Weekly on ${days} at ${time}`;
            }
            case 'monthly':
                return `Monthly on day ${this.dayOfMonth} at ${time}`;
            default:
                return this.cronExpression;
        }
    },
});
