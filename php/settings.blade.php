<!-- resources/views/settings.blade.php -->
@extends('layouts.app')

@section('content')

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<div class="d-flex flex-row container-fluid justify-content-between">
    <div class="col-12 col-sm-8 col-md-6 col-lg-4">
        <form action="{{ url('/update-env') }}" method="POST">
            @csrf

            <button type="submit" class="btn btn-primary">Save</button>

            @foreach ($config as $key => $value)
            <div>
                <x-forms.input-text label="{{ $key }}" name="{{ $key }}" :value="$value"></x-forms.input-text>
            </div>
            @endforeach

        </form>
    </div>

    <div>
        <form class="container g-3 border rounded-4 d-flex flex-column" action="{{ url('/table-column') }}"
            method="POST">
            @csrf
            <div class="row p-3">
                <x-forms.input-select label="table_list" class="col-sm-12 col-md-6 col-xxl-3" name="table_list"
                    id="table_list" :options="$table_list" :selected-value="session('table_list') ?? old('table_list')">
                </x-forms.input-select>
                <x-forms.input-select label="column" class="col-sm-12 col-md-6 col-xxl-3" name="column" id="column"
                    :options :selected-value></x-forms.input-select>
                <x-forms.input-select label="this_value" class="col-sm-12 col-md-6 col-xxl-3" name="this_value"
                    id="this_value" :options :selected-value></x-forms.input-select>
                <x-forms.input-text label="to_value" class="col-sm-12 col-md-6 col-xxl-3" name="to_value" id="to_value"
                    value>
                </x-forms.input-text>
            </div>
            <div class="col p-3">
                <button type="submit" name="soft-delete" class="btn btn-primary" value="soft-delete">論理削除</button>
                <button type="submit" name="hard-delete" class="btn btn-primary" value="hard-delete">物理削除</button>
                <button type="submit" name="update" class="btn btn-primary" value="update">update</button>
                <button type="submit" name="rollback" class="btn btn-primary" value="rollback">Rollback</button>
            </div>
        </form>

        <form class="container border rounded-4 d-flex flex-column" action="{{ url('/updateDB') }}" method="POST">
            @csrf

            <x-forms.input-text label="table name" name="table_name"
                value="{{ session('table_name', old('table_name', $table_name ?? '')) }}">
            </x-forms.input-text>

            <div class="p-3 d-flex flex-column" style="width: 700px;">
                <div class="p-1 m-1 d-flex justify-content-between">
                    <button type="submit" name="operate" class="btn btn-primary" value="deleted_at;deleted">deleted_at
                        =>
                        deleted</button>
                    <button type="submit" name="operate" class="btn btn-primary" value="deleted;deleted_at">deleted =>
                        deleted_at</button>
                </div>
                <div class="p-1 m-1 d-flex justify-content-between">
                    <button type="submit" name="operate" class="btn btn-primary" value="created_at;created">created_at
                        =>
                        created</button>
                    <button type="submit" name="operate" class="btn btn-primary" value="created;created_at">created =>
                        created_at</button>
                </div>
                <div class="p-1 m-1 d-flex justify-content-between">
                    <button type="submit" name="operate" class="btn btn-primary" value="updated_at;updated">updated_at
                        =>
                        updated</button>
                    <button type="submit" name="operate" class="btn btn-primary" value="updated;updated_at">updated =>
                        updated_at</button>
                </div>
                <div class="p-1 m-1 d-flex justify-content-between">
                    <button type="submit" name="operate" class="btn btn-primary"
                        value="table_names;table_name">table_names
                        =>
                        table_name</button>
                    <button type="submit" name="operate" class="btn btn-primary"
                        value="table_name;table_names">table_name
                        =>
                        table_names</button>
                </div>
            </div>
        </form>

        <form class="container border rounded-4 d-flex flex-row justify-content-center" action="{{ url('/dump') }}"
            method="POST">
            @csrf
            <div class="p-3 m-3">
                <x-forms.input-text label="case" name="case" value="{{ session('case', old('case', $case ?? '')) }}">
                </x-forms.input-text>
                <x-forms.input-radio label="before-or-after" name="before-or-after" :options="$AorB"
                    :checked-value="$AorB['before']" required>
                </x-forms.input-radio>
                <div>
                    <button type="submit" name="dump" class="btn btn-primary" value="dump"
                        style="margin-top:8px">DUMP</button>
                </div>
            </div>
        </form>
        <div class="d-flex justify-content-between">
            <form action="{{ url('/refreshdb') }}" method="POST">
                @csrf
                <button type="submit" name="refreshDB" class="btn btn-primary" value="refreshDB"
                    style="margin-top:8px">clearDB and migration</button>
            </form>
            <form action="{{ url('/migration') }}" method="POST">
                @csrf
                <button type="submit" name="migration" class="btn btn-primary" value="migration" id="migration"
                    style="margin-top:8px;">migration</button>
            </form>
        </div>
        <div class="container border rounded-4 d-flex flex-row justify-content-center mt-3">
            <form action="{{ url('/shower') }}" class="m-3" method="POST" target="_blank">

                <div id="er">
                    <div id="add-table" class="btn btn-primary mb-3">add table</div>
                    <div id="er-tables">
                        <x-forms.input-select class="dropdown show" name="tables[]" :options="$table_list" id="add-table-select">
                        </x-forms.input-select>
                    </div>
                    <x-forms.textarea label="tables" class="show" name="tables[]" id="tables">
                    </x-forms.textarea>
                </div>
                <button type="submit" name="showER" class="btn btn-primary" value="showER" id="showER"
                    style="margin-top:8px;">showER</button>
            </form>
        </div>

        <div class="container border rounded-4 d-flex flex-row justify-content-center mt-3">
            <form action="{{ url('/showenum') }}" class="m-3" method="POST" target="_blank">
                <x-forms.textarea label="enums" class="" name="enums[]" id="enums">
                </x-forms.textarea>

                <button type="submit" name="showenum" class="btn btn-primary" value="showenum" id="showenum"
                    style="margin-top:8px;">showenum</button>
            </form>
        </div>
        <div class="container border rounded-4 d-flex flex-row justify-content-center mt-3">
            <form action="{{ url('/reflect') }}" class="m-3" method="POST" target="_blank">
                @csrf

                <div class="d-flex flex-row" id="classes">
                </div>

                <x-forms.input-select name="class" id="class-template" style="display: none">
                </x-forms.input-select>
                <button type="submit" name="reflect" class="btn btn-primary m-3" value="reflect" id="reflect"
                    style="margin-top:8px;">reflect</button>
            </form>
        </div>
        <div class="container border rounded-4 d-flex flex-column justify-content-center mt-3">
            <form id="showTablesForm" class="m-3" method="GET">
                <x-forms.textarea label="showTables" name="showTables[]" id="showTables"></x-forms.textarea>

            </form>
            <button class="btn btn-primary" style="margin-top:8px;" id="showTablesBtn">showTables</button>
        </div>
    </div>
</div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.0/gsap.min.js"></script>

<script>
    const addButton = document.getElementById("add-table");
    const erTables = document.getElementById("er-tables");

    addButton.addEventListener("click", addSelect);

    function addSelect() {
        const originalElement = document.querySelector('#add-table-select');
        const clonedElement = originalElement.cloneNode(true);
        clonedElement.removeAttribute('id');

        erTables.appendChild(clonedElement);
    }
</script>
<script>
    const progress = document.getElementById("progress");
    const table_list = document.getElementById("table_list");
    const column = document.getElementById("column");
    const this_value = document.getElementById("this_value");
    const to_value = document.getElementById("to_value");

    function updateSelectOptions(selectElement, data, useIndex = false) {
        $(selectElement).empty();

        let index = 0;
        data.forEach(item => {
            const option = useIndex
                ? new Option(`record ${index}: ${item}`, index)
                : new Option(item, item);
            $(selectElement).append(option);
            index ++;
        });

        const defaultValue = $(selectElement).data('id');
        if (defaultValue) {
            $(selectElement).val(defaultValue);
        }
    }

    $(document).ready(function () {
        $('#table_list').change(function() {
            let selectedValue = $(this).val();

            $.ajax({
                url: '/editCol',
                type: 'GET',
                data: {
                    table_list: selectedValue,
                },
                success: function(response) {
                    updateSelectOptions(column, response.columns,);
                    updateSelectOptions(this_value, response.values);
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON.error);
                }
            });
        });
    });

    $(document).ready(function () {
        $('#column').change(function() {
            let selectedValue = $(this).val();
            let tableValue = $('#table_list').val();

            $.ajax({
                url: '/editValue',
                type: 'GET',
                data: {
                    column: selectedValue,
                    table_list: tableValue,
                },
                success: function(response) {
                    updateSelectOptions(this_value, response.values);
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON.error);
                }
            });
        });
    });

    if (table_list.value){
        $.ajax({
            url: '/editCol',
            type: 'GET',
            data: {
                table_list: table_list.value,
            },
            success: function(response) {
                updateSelectOptions(column, response.columns,);
                updateSelectOptions(this_value, response.values);
            },
            error: function(xhr) {
                console.log(xhr.responseJSON.error);
            }
        });
    }

    $(document).ready(function() {
        var alertText = $("div.alert.alert-success").text();

        if (alertText.includes("DB dropped and then created! Now you need migration")) {
            $("#migration").click();
        }
    });

    $(document).ready(function () {
        $.ajax({
            url: '/getclasses',
            type: 'GET',
            success: function(response) {
                sessionStorage.setItem('classData', JSON.stringify(response));
                CascadingMenuInit();
            },
            error: function(xhr) {
                console.log(xhr.responseJSON.error);
            }
        });
    });

</script>
<script>
    let menuData = {};
    let lengthOfMenuData;
    const classContainer = document.getElementById("classes");
    const template = document.getElementById("class-template");

    function CascadingMenuInit(){
        let data = JSON.parse(sessionStorage.getItem('classData'));

        packageData(data);
        renderCascadingMenu();
    }

    function packageData(data, i = 0) {
        const [firstKey, firstValue] = Object.entries(data)[0];

        menuData[i] = {
            selected: firstKey,
            options: data,
        };

        if (typeof firstValue !== 'number') {
            packageData(firstValue, i + 1);
        }
    }

    function renderCascadingMenu(){
        gsap.to(classContainer.children, {
            opacity: 0,
            duration: 0.5,
            ease: "power2.out",
            onComplete: () => {
                classContainer.innerHTML = "";
                Object.entries(menuData).forEach(([key, menu]) => {
                    const select = template.cloneNode(true);
                    select.style.display = "block";
                    select.id = `level-${key}`;

                    const options = Object.entries(menu.options);
                    options.forEach(([key, value]) => {
                        select.appendChild(new Option(key, (typeof value == "object") ? key : value));
                    });
                    select.value = menu.selected;

                    classContainer.appendChild(select);
                    gsap.from(select, {
                        opacity: 0,
                        y: -50,
                        duration: 0.5,
                        ease: "power2.out"
                    });

                    select.addEventListener("change", changeMenuData.bind(null, select));
                });
            }
        });
    }

    function changeMenuData(element) {
        const id = element.id;
        const level = Number(id.replace("level-", ""));
        const value = element.value;

        lengthOfMenuData = Object.keys(menuData).length;

        updateMenuData(level, value);

        const nowLength = Object.keys(menuData).length;

        if (nowLength == lengthOfMenuData){
            const menuDataSelected = menuData[nowLength-1].selected;
            const pageSelected = document.getElementById(`level-${nowLength-1}`).value;
        }

        if (!(lengthOfMenuData == nowLength)){
            renderCascadingMenu();
            return;
        }
        if (menuData[nowLength-1].selected == null){
            renderCascadingMenu();
            return;
        }
        if (!(menuDataSelected == pageSelected)){
            renderCascadingMenu();
            return;
        }
    }

    function updateMenuData(level, value) {
        menuData[level].selected = value;

        removeHighLevel(level);

        const nextLevel = level + 1;
        if (menuData[level].options[value] && typeof menuData[level].options[value] === 'object') {
            menuData[nextLevel] = {
                selected: null,
                options: menuData[level].options[value],
            };
        }

        function removeHighLevel(level) {
            ++level;
            if (menuData.hasOwnProperty(level)) {
                delete menuData[level];
                removeHighLevel(level);
            }
        }
    }
</script>
<script>
    document.getElementById('showTablesBtn').addEventListener('click', function(e) {
        $.ajax({
            url: '/showTables',
            type: 'GET',
            data: {
                showTables: document.getElementById('showTables').value,
            },
            success: function(data) {
                data.forEach(table => {
                    window.open(`http://localhost:8080/?server=mysql&username=as&db=sss&select=${table}`, '_blank');
                });
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    });
</script>
@endsection
