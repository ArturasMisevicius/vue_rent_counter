const calendarModalInstances = new WeakSet();

const dateFormatterCache = new Map();

const pad = (value) => String(value).padStart(2, '0');

const dateKey = (date) => [
    date.getFullYear(),
    pad(date.getMonth() + 1),
    pad(date.getDate()),
].join('-');

const dateTimeKey = (date, includeSeconds) => [
    dateKey(date),
    [
        pad(date.getHours()),
        pad(date.getMinutes()),
        includeSeconds ? pad(date.getSeconds()) : null,
    ].filter((value) => value !== null).join(':'),
].join(' ');

const parseDate = (value) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }

    return date;
};

const parseCalendarValue = (value) => {
    if (!value) {
        return null;
    }

    const normalized = value.replace('T', ' ');
    const [datePart, timePart = ''] = normalized.split(' ');
    const date = parseDate(datePart);

    if (!date) {
        return null;
    }

    if (timePart === '') {
        return date;
    }

    const matches = timePart.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);

    if (!matches) {
        return date;
    }

    const [, hours, minutes, seconds = '00'] = matches;
    date.setHours(Number(hours), Number(minutes), Number(seconds), 0);

    return date;
};

const sameDate = (left, right) => (
    left.getFullYear() === right.getFullYear()
    && left.getMonth() === right.getMonth()
    && left.getDate() === right.getDate()
);

const dateOnly = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

const formatDate = (date, locale, hasTime) => {
    const key = `${locale}:${hasTime ? 'datetime' : 'date'}`;

    if (!dateFormatterCache.has(key)) {
        dateFormatterCache.set(key, new Intl.DateTimeFormat(locale, hasTime ? {
            dateStyle: 'long',
            timeStyle: 'short',
        } : {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }));
    }

    return dateFormatterCache.get(key).format(date);
};

const formatMonth = (date, locale) => {
    const key = `${locale}:month`;

    if (!dateFormatterCache.has(key)) {
        dateFormatterCache.set(key, new Intl.DateTimeFormat(locale, {
            month: 'long',
            year: 'numeric',
        }));
    }

    return dateFormatterCache.get(key).format(date);
};

const weekdayLabels = (locale, weekStartsOn) => Array.from({ length: 7 }, (_, index) => {
    const date = new Date(2026, 1, 1 + ((weekStartsOn + index) % 7));

    return new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date);
});

const dispatchInputEvents = (element) => {
    ['input', 'change'].forEach((eventName) => {
        element.dispatchEvent(new Event(eventName, { bubbles: true }));
    });
};

const normalizedNumber = (value, fallback, min, max) => {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return fallback;
    }

    return Math.min(Math.max(number, min), max);
};

const focusFirstCalendarDay = (days) => {
    const selectedDay = days.querySelector('[aria-pressed="true"]');
    const availableDay = days.querySelector('button:not(:disabled)');

    (selectedDay || availableDay)?.focus();
};

export const initializeCalendarModals = () => {
    document.querySelectorAll('[data-calendar-picker]').forEach((picker) => {
        if (calendarModalInstances.has(picker)) {
            return;
        }

        calendarModalInstances.add(picker);

        const input = picker.querySelector('[data-calendar-input]');
        const trigger = picker.querySelector('[data-calendar-trigger]');
        const display = picker.querySelector('[data-calendar-display]');
        const dialog = picker.querySelector('[data-calendar-dialog]');
        const monthTitle = picker.querySelector('[data-calendar-month]');
        const weekdays = picker.querySelector('[data-calendar-weekdays]');
        const days = picker.querySelector('[data-calendar-days]');
        const selectedLabel = picker.querySelector('[data-calendar-selected]');
        const previousButton = picker.querySelector('[data-calendar-previous]');
        const nextButton = picker.querySelector('[data-calendar-next]');
        const todayButton = picker.querySelector('[data-calendar-today]');
        const hourInput = picker.querySelector('[data-calendar-hour]');
        const minuteInput = picker.querySelector('[data-calendar-minute]');
        const secondInput = picker.querySelector('[data-calendar-second]');
        const closeButtons = picker.querySelectorAll('[data-calendar-close]');

        if (!(input instanceof HTMLInputElement)
            || !(trigger instanceof HTMLButtonElement)
            || !(dialog instanceof HTMLDialogElement)
            || !display
            || !monthTitle
            || !weekdays
            || !days
            || !selectedLabel
            || !(previousButton instanceof HTMLButtonElement)
            || !(nextButton instanceof HTMLButtonElement)
            || !(todayButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        const locale = picker.dataset.locale || document.documentElement.lang || 'en';
        const weekStartsOn = Number(picker.dataset.weekStartsOn || 1);
        const maxDate = parseDate(picker.dataset.maxDate || '');
        const minDate = parseDate(picker.dataset.minDate || '');
        const hasTime = picker.dataset.mode === 'datetime';
        const includeSeconds = picker.dataset.includeSeconds === 'true';
        const emptyLabel = picker.dataset.emptyLabel || '';
        const selectDateLabel = picker.dataset.selectDateLabel || '';

        let selectedDate = parseCalendarValue(input.value) || new Date();
        let visibleMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);

        const clampToBounds = (date) => {
            if (minDate && dateOnly(date) < dateOnly(minDate)) {
                return new Date(minDate);
            }

            if (maxDate && dateOnly(date) > dateOnly(maxDate)) {
                return new Date(maxDate);
            }

            return new Date(date);
        };

        const setOpenState = (open) => {
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        const syncTimeControls = () => {
            if (!hasTime) {
                return;
            }

            if (hourInput instanceof HTMLInputElement) {
                hourInput.value = pad(selectedDate.getHours());
            }

            if (minuteInput instanceof HTMLInputElement) {
                minuteInput.value = pad(selectedDate.getMinutes());
            }

            if (secondInput instanceof HTMLInputElement) {
                secondInput.value = pad(selectedDate.getSeconds());
            }
        };

        const updateDisplay = () => {
            const date = parseCalendarValue(input.value);
            const label = date ? formatDate(date, locale, hasTime) : emptyLabel;

            display.textContent = label;
            selectedLabel.textContent = label;
        };

        const writeInputValue = () => {
            input.value = hasTime ? dateTimeKey(selectedDate, includeSeconds) : dateKey(selectedDate);
            dispatchInputEvents(input);
            updateDisplay();
        };

        const close = () => {
            if (dialog.open) {
                dialog.close();
            }

            setOpenState(false);
            trigger.focus();
        };

        const render = () => {
            monthTitle.textContent = formatMonth(visibleMonth, locale);
            weekdays.replaceChildren();
            days.replaceChildren();
            syncTimeControls();

            weekdayLabels(locale, weekStartsOn).forEach((label) => {
                const cell = document.createElement('span');
                cell.className = 'text-center text-xs font-semibold uppercase tracking-normal text-slate-500';
                cell.textContent = label;
                weekdays.append(cell);
            });

            const year = visibleMonth.getFullYear();
            const month = visibleMonth.getMonth();
            const startOffset = (new Date(year, month, 1).getDay() - weekStartsOn + 7) % 7;
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let index = 0; index < startOffset; index += 1) {
                days.append(document.createElement('span'));
            }

            for (let day = 1; day <= daysInMonth; day += 1) {
                const date = new Date(year, month, day);
                const disabled = (minDate && dateOnly(date) < dateOnly(minDate))
                    || (maxDate && dateOnly(date) > dateOnly(maxDate));
                const selected = sameDate(date, selectedDate);
                const today = sameDate(date, new Date());
                const button = document.createElement('button');

                button.type = 'button';
                button.textContent = String(day);
                button.disabled = disabled;
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                button.setAttribute('aria-label', `${selectDateLabel} ${formatDate(date, locale, false)}`);
                button.className = [
                    'inline-flex min-h-11 min-w-11 items-center justify-center rounded-2xl text-sm font-semibold transition',
                    selected ? 'bg-brand-ink text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100',
                    today && !selected ? 'ring-2 ring-brand-mint/50' : '',
                    disabled ? 'cursor-not-allowed opacity-35 hover:bg-transparent' : '',
                ].filter(Boolean).join(' ');

                button.addEventListener('click', () => {
                    selectedDate.setFullYear(date.getFullYear(), date.getMonth(), date.getDate());
                    writeInputValue();
                    render();

                    if (!hasTime) {
                        close();
                    }
                });

                days.append(button);
            }

            previousButton.disabled = minDate && new Date(year, month, 0) < dateOnly(minDate);
            nextButton.disabled = maxDate && new Date(year, month + 1, 1) > dateOnly(maxDate);
            updateDisplay();
        };

        const updateTime = () => {
            if (!hasTime) {
                return;
            }

            selectedDate.setHours(
                normalizedNumber(
                    hourInput instanceof HTMLInputElement ? hourInput.value : selectedDate.getHours(),
                    selectedDate.getHours(),
                    0,
                    23,
                ),
                normalizedNumber(
                    minuteInput instanceof HTMLInputElement ? minuteInput.value : selectedDate.getMinutes(),
                    selectedDate.getMinutes(),
                    0,
                    59,
                ),
                normalizedNumber(
                    secondInput instanceof HTMLInputElement ? secondInput.value : selectedDate.getSeconds(),
                    selectedDate.getSeconds(),
                    0,
                    59,
                ),
                0,
            );
            writeInputValue();
            syncTimeControls();
        };

        trigger.addEventListener('click', () => {
            selectedDate = clampToBounds(parseCalendarValue(input.value) || new Date());
            visibleMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            render();

            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }

            setOpenState(true);
            focusFirstCalendarDay(days);
        });

        previousButton.addEventListener('click', () => {
            visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
            render();
            focusFirstCalendarDay(days);
        });

        nextButton.addEventListener('click', () => {
            visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
            render();
            focusFirstCalendarDay(days);
        });

        todayButton.addEventListener('click', () => {
            selectedDate = clampToBounds(new Date());
            visibleMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            writeInputValue();
            render();

            if (!hasTime) {
                close();
            }
        });

        [hourInput, minuteInput, secondInput].forEach((timeInput) => {
            if (timeInput instanceof HTMLInputElement) {
                timeInput.addEventListener('change', updateTime);
            }
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', close);
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                close();
            }
        });

        dialog.addEventListener('close', () => setOpenState(false));
        input.addEventListener('change', () => {
            selectedDate = clampToBounds(parseCalendarValue(input.value) || selectedDate);
            visibleMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            render();
        });

        render();
    });
};
