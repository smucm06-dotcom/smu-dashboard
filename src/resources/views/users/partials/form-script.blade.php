<script>
    // Shows the chosen department's initiatives, with search, select all and a list of what is selected.
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('umRole');
        const panel = document.getElementById('umPanel');
        const tools = document.getElementById('umTools');
        const search = document.getElementById('umPickerSearch');
        const selectAll = document.getElementById('umSelectAll');
        const count = document.getElementById('umCount');
        const chips = document.getElementById('umChips');
        const noteNone = document.getElementById('umNoteNone');
        const noteAdmin = document.getElementById('umNoteAdmin');
        const noteMoved = document.getElementById('umNoteMoved');
        const groups = Array.from(document.querySelectorAll('.um-group'));
        const savedRole = roleSelect.dataset.savedRole;
        const adminRole = @json(\App\Models\Role::ADMIN);

        function activeGroup() {
            return groups.find(function (group) { return ! group.hidden; }) || null;
        }

        function options(group) {
            return Array.from(group.querySelectorAll('.um-option'));
        }

        function checkboxes(group) {
            return Array.from(group.querySelectorAll('input[type="checkbox"]'));
        }

        function refresh() {
            const group = activeGroup();

            if (! group) {
                return;
            }

            const all = checkboxes(group);
            const checked = all.filter(function (box) { return box.checked; });
            const visible = options(group)
                .filter(function (option) { return ! option.hidden; })
                .map(function (option) { return option.querySelector('input'); });
            const visibleChecked = visible.filter(function (box) { return box.checked; });

            count.textContent = checked.length + ' of ' + all.length + ' selected';
            selectAll.disabled = visible.length === 0;
            selectAll.checked = visible.length > 0 && visibleChecked.length === visible.length;
            selectAll.indeterminate = visibleChecked.length > 0 && visibleChecked.length < visible.length;

            chips.replaceChildren();
            checked.forEach(function (box) {
                const chip = document.createElement('button');
                const remove = document.createElement('span');

                chip.type = 'button';
                chip.className = 'um-chip';
                chip.title = 'Remove ' + box.dataset.label;
                chip.append(box.dataset.label);
                remove.className = 'um-chip-x';
                remove.setAttribute('aria-hidden', 'true');
                remove.textContent = '×';
                chip.append(remove);
                chip.addEventListener('click', function () {
                    box.checked = false;
                    refresh();
                });
                chips.append(chip);
            });
            chips.hidden = checked.length === 0;
        }

        function applySearch() {
            const group = activeGroup();

            if (! group) {
                return;
            }

            const text = search.value.trim().toLowerCase();
            let matches = 0;

            options(group).forEach(function (option) {
                option.hidden = ! option.dataset.search.includes(text);
                matches += option.hidden ? 0 : 1;
            });

            const empty = group.querySelector('[data-empty="search"]');

            if (empty) {
                empty.hidden = matches > 0;
            }
        }

        function showRole() {
            const role = roleSelect.value;
            const isDepartment = role !== '' && role !== adminRole;

            groups.forEach(function (group) {
                const isChosen = group.dataset.role === role;

                group.hidden = ! isChosen;
                group.disabled = ! isChosen;
            });

            panel.hidden = ! isDepartment;
            noteNone.hidden = role !== '';
            noteAdmin.hidden = role !== adminRole;
            noteMoved.hidden = ! savedRole || savedRole === adminRole || role === savedRole;

            const group = activeGroup();

            tools.hidden = ! group || options(group).length === 0;
            search.value = '';
            applySearch();
            refresh();
        }

        roleSelect.addEventListener('change', showRole);
        search.addEventListener('input', function () {
            applySearch();
            refresh();
        });

        // Enter in the search box must not save the form.
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        // Applies to the initiatives showing, so a search can be selected on its own.
        selectAll.addEventListener('change', function () {
            options(activeGroup())
                .filter(function (option) { return ! option.hidden; })
                .forEach(function (option) { option.querySelector('input').checked = selectAll.checked; });
            refresh();
        });

        groups.forEach(function (group) {
            group.addEventListener('change', refresh);
        });

        showRole();
    });
</script>
