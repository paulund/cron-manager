import './bootstrap';
import scheduleBuilder from './schedule-builder';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('scheduleBuilder', scheduleBuilder);

    window.Alpine.data('envVarEditor', (initial = []) => ({
        pairs: initial.length > 0 ? initial : [],

        addPair() {
            this.pairs.push({ key: '', value: '' });
        },

        removePair(index) {
            this.pairs.splice(index, 1);
        },
    }));

    window.Alpine.data('tagEditor', (selected = [], suggestions = []) => ({
        selected,
        suggestions,
        input: '',

        add() {
            const tag = this.input.trim().replace(/,+$/, '').trim();
            if (tag && !this.selected.includes(tag)) {
                this.selected.push(tag);
            }
            this.input = '';
        },

        remove(index) {
            this.selected.splice(index, 1);
        },
    }));
});
