<script>
    // The role choice, the departments ticked, and each ticked department's initiatives, with search, Select
    // all for each department, and a list of everything selected.
    document.addEventListener('DOMContentLoaded', function () {
        const picker = document.getElementById('umPicker');
        const accessInputs = Array.from(document.querySelectorAll('input[name="access"]'));
        const steps = document.getElementById('umSteps');
        const noteNone = document.getElementById('umNoteNone');
        const noteAdmin = document.getElementById('umNoteAdmin');
        const noteMoved = document.getElementById('umNoteMoved');
        const deptSearch = document.getElementById('umDeptSearch');
        const deptCount = document.getElementById('umDeptCount');
        const deptEmpty = document.getElementById('umDeptEmpty');
        const deptOptions = Array.from(document.querySelectorAll('#umDeptList .um-option'));
        const deptBoxes = deptOptions.map(function (option) { return option.querySelector('input'); });
        const tools = document.getElementById('umTools');
        const search = document.getElementById('umPickerSearch');
        const count = document.getElementById('umCount');
        const chips = document.getElementById('umChips');
        const noteNoDepartment = document.getElementById('umNoteNoDepartment');
        const groups = Array.from(document.querySelectorAll('.um-groups .um-group'));
        const savedAccess = picker.dataset.savedAccess;
        const savedDepartments = JSON.parse(picker.dataset.savedDepartments || '[]');
        const adminAccess = @json(\App\Http\Requests\UserRequest::ADMIN);
        const departmentAccess = @json(\App\Http\Requests\UserRequest::DEPARTMENTS);

        function access() {
            const chosen = accessInputs.find(function (input) { return input.checked; });

            return chosen ? chosen.value : '';
        }

        function plural(n, one, many) {
            return n + ' ' + (n === 1 ? one : many);
        }

        function openGroups() {
            return groups.filter(function (group) { return ! group.hidden; });
        }

        function options(group) {
            return Array.from(group.querySelectorAll('.um-option'));
        }

        function boxes(group) {
            return options(group).map(function (option) { return option.querySelector('input'); });
        }

        // Counts, each department's Select all, and the list of selected initiatives.
        function refresh() {
            const open = openGroups();
            let total = 0;
            let selected = 0;

            chips.replaceChildren();
            open.forEach(function (group) {
                const all = boxes(group);
                const checked = all.filter(function (box) { return box.checked; });
                const visible = options(group).filter(function (option) { return ! option.hidden; }).map(function (option) { return option.querySelector('input'); });
                const visibleChecked = visible.filter(function (box) { return box.checked; });
                const selectAll = group.querySelector('.um-group-all');

                total += all.length;
                selected += checked.length;
                group.querySelector('.um-group-count').textContent = all.length ? checked.length + ' of ' + all.length + ' selected' : '';

                if (selectAll) {
                    selectAll.disabled = visible.length === 0;
                    selectAll.checked = visible.length > 0 && visibleChecked.length === visible.length;
                    selectAll.indeterminate = visibleChecked.length > 0 && visibleChecked.length < visible.length;
                }

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
            });

            count.textContent = open.length ? selected + ' of ' + plural(total, 'initiative', 'initiatives') + ' selected' : '';
            chips.hidden = selected === 0;

            const ticked = deptBoxes.filter(function (box) { return box.checked; }).length;
            deptCount.textContent = plural(ticked, 'department', 'departments') + ' selected';
        }

        function applyDepartmentSearch() {
            const text = deptSearch.value.trim().toLowerCase();
            let matches = 0;

            deptOptions.forEach(function (option) {
                option.hidden = ! option.dataset.search.includes(text);
                matches += option.hidden ? 0 : 1;
            });
            deptEmpty.hidden = matches > 0;
        }

        function applySearch() {
            const text = search.value.trim().toLowerCase();

            openGroups().forEach(function (group) {
                let matches = 0;

                options(group).forEach(function (option) {
                    option.hidden = ! option.dataset.search.includes(text);
                    matches += option.hidden ? 0 : 1;
                });

                const empty = group.querySelector('[data-empty="search"]');

                if (empty) {
                    empty.hidden = matches > 0;
                }
            });
        }

        // A department's initiatives show, and are sent, only while the department is ticked.
        function showDepartments() {
            const ticked = deptBoxes.filter(function (box) { return box.checked; }).map(function (box) { return box.value; });

            groups.forEach(function (group) {
                const isTicked = ticked.indexOf(group.dataset.role) >= 0;

                group.hidden = ! isTicked;
                group.disabled = ! isTicked;
            });

            noteNoDepartment.hidden = ticked.length > 0;
            tools.hidden = ticked.length === 0;
            showMoved(ticked);
            applySearch();
            refresh();
        }

        // Warns when saving would take away a department the user has now.
        function showMoved(ticked) {
            const removed = access() === departmentAccess
                ? savedDepartments.filter(function (name) { return ticked.indexOf(name) < 0; })
                : [];

            const names = removed.map(function (name) {
                const box = deptBoxes.find(function (b) { return b.value === name; });

                return box ? box.dataset.label : name;
            });

            noteMoved.hidden = names.length === 0;
            noteMoved.textContent = names.length
                ? 'Saving will remove this user from ' + (names.length === 1 ? names[0] : names.slice(0, -1).join(', ') + ' and ' + names[names.length - 1]) + ', and from its initiatives.'
                : '';

            if (savedAccess === departmentAccess && access() === adminAccess) {
                noteMoved.hidden = false;
                noteMoved.textContent = 'Saving will make this user an administrator, who can open and manage everything.';
            }
        }

        function showAccess() {
            const value = access();

            noteNone.hidden = value !== '';
            noteAdmin.hidden = value !== adminAccess;
            steps.hidden = value !== departmentAccess;

            // An administrator's form sends no departments or initiatives.
            deptBoxes.forEach(function (box) { box.disabled = value !== departmentAccess || box.closest('fieldset').disabled; });

            showDepartments();
        }

        // A department just ticked has its initiatives put first, right under "2. Initiatives", and briefly
        // highlighted, so they never appear out of sight below the other departments' lists.
        const groupsBox = document.querySelector('.um-groups');

        function bringForward(box) {
            const group = groups.find(function (g) { return g.dataset.role === box.value; });

            if (! group) {
                return;
            }

            groupsBox.prepend(group);
            group.classList.remove('is-new');
            void group.offsetWidth;
            group.classList.add('is-new');

            const bottom = group.getBoundingClientRect().top + Math.min(group.offsetHeight, 160);

            if (bottom > window.innerHeight) {
                group.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }

        accessInputs.forEach(function (input) { input.addEventListener('change', showAccess); });
        deptBoxes.forEach(function (box) {
            box.addEventListener('change', function () {
                showDepartments();

                if (box.checked) {
                    bringForward(box);
                    refresh();
                }
            });
        });
        deptSearch.addEventListener('input', applyDepartmentSearch);
        search.addEventListener('input', function () {
            applySearch();
            refresh();
        });

        // Enter in a search box must not save the form.
        [deptSearch, search].forEach(function (box) {
            box.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });
        });

        groups.forEach(function (group) {
            const selectAll = group.querySelector('.um-group-all');

            // Applies to the department's initiatives showing, so a search can be selected on its own.
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    options(group)
                        .filter(function (option) { return ! option.hidden; })
                        .forEach(function (option) { option.querySelector('input').checked = selectAll.checked; });
                    refresh();
                });
            }

            group.addEventListener('change', function (event) {
                if (! event.target.classList.contains('um-group-all')) {
                    refresh();
                }
            });
        });

        showAccess();
    });
</script>
