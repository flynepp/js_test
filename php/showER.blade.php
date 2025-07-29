<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ER picture</title>
    <script src="https://cdn.jsdelivr.net/npm/jsplumb@2.15.6/dist/js/jsplumb.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .node {
            padding: 20px;
            background-color: #f0f0f0;
            border: 1px solid #aaa;
            text-align: center;
            border-radius: 5px;
            position: absolute;
        }

        .node {
            user-select: none;
        }

        .node * {
            user-select: text;
        }

        #container {
            position: relative;
            width: 100%;
            height: 100vh;
            background-color: #f9f9f9;
        }

        .column {
            border: 1px solid #000;
            padding-left: 1em;
            padding-right: 1em;
        }

        .connection {
            border: 1px solid #000;
        }
    </style>
</head>

<body>
    <h1>ER</h1>
    <img id="img" src=""
        style="z-index: 50; position: absolute; width: 32px; height: 32px; pointer-events: none" />
    <div>
        <button id="show-all-cols">show all cols</button>
        <input id="tables-input" type="text" style="width: 10%" />
        <button id="add-table">add table</button>
    </div>

    <div id="container"></div>
    <script>
        let foreignKeys = @json(session('fkData'));

                const container = document.getElementById("container");

                const mapping = {
                    "LL": ["Left", "Left"],
                    "RR": ["Right", "Right"],
                    "LR": ["Left", "Right"],
                    "RL": ["Right", "Left"],
                };

                let instance;
                let f = -6000;
                let lockTime = 1000;
                const tableCount = Object.keys(data).length;
                let F = f / tableCount;

                function get_el_info(el_name) {
                    let el = document.getElementById(el_name);
                    return el.getBoundingClientRect();
                }

                function get_anchors(child, father) {
                    const childLeft = child.left;
                    const childRight = child.left+child.width;
                    const fatherLeft = father.left;
                    const fatherRight = father.left+father.width;

                    const result = {
                        "LL": Math.abs(childLeft - fatherLeft),
                        "RR": Math.abs(childRight - fatherRight),
                        "LR": Math.abs(childLeft - fatherRight),
                        "RL": Math.abs(childRight - fatherLeft),
                    };

                    const minKey = Object.entries(result).reduce((min, current) =>
                        current[1] < min[1] ? current : min
                    )[0];

                    return mapping[minKey];
                }

                function newNode(data) {
                    // create new node
                    const node = document.createElement("div");

                    // set the base attributes
                    node.classList.add("node");
                    node.style.top = `${data.pos.top}px`;
                    node.style.left = `${data.pos.left}px`;
                    node.id = data.table;

                    // set table name
                    const tableNameDiv = document.createElement("div");

                    const url = document.createElement("a");
                    url.href = `http://localhost:8080/?server=mysql&username=ss&db=ss&select=${data.table}`;
                    url.target = "_blank";
                    url.innerText = data.jpTable;
                    tableNameDiv.appendChild(url);

                    // set table name
                    const enName = document.createElement("span")
                    enName.innerText = `\n(${data.table})`;
                    tableNameDiv.appendChild(enName);

                    node.appendChild(tableNameDiv);

                    //set each columns
                    Object.entries(data.columns).forEach(([key, column]) => {
                        const newCol = document.createElement("div");

                        newCol.id = `${data.table}-${column.name}`;
                        newCol.classList.add("column");
                        newCol.innerText = column.name;

                        // need show?
                        if (column.show){
                            newCol.style.display = "block";
                        } else {
                            newCol.style.display = "none";
                        }

                        node.appendChild(newCol);
                    });

                    return node;
                }

                function init_connection(instance) {
                    instance.deleteEveryConnection();

                    foreignKeys.forEach((fk) => {
                        if (typeof fk === "undefined") return;

                        const child = get_el_info(fk.child);
                        const father = get_el_info(fk.father);

                        instance.connect({
                            source: `${fk.child}-${fk.childCol}`,
                            target: `${fk.father}-${fk.fatherCol}`,
                            connector: ["Bezier"],
                            anchors: get_anchors(child, father),
                            endpoint: "Blank",
                            paintStyle: { stroke: "#000", strokeWidth: 2 },
                            overlays: [
                                ["Arrow", { width: 10, length: 10, location: 1 }],
                            ],
                        });
                    });
                }

                function render (instance) {
                    container.innerHTML = "";

                    jsPlumb.ready(function (instance) {
                        instance = jsPlumb.getInstance({
                            Container: "container",
                        });

                        Object.entries(data).forEach(([key, table]) => {
                            const node = newNode(table);
                            container.appendChild(node);

                            instance.draggable(node);
                            node.addEventListener("mousedown", (event) => {
                                if (event.target !== node) {
                                    event.stopPropagation();
                                }
                            });

                            const observer = new MutationObserver(() => {
                                table.pos.top = parseInt(node.style.top, 10) || 0;
                                table.pos.left = parseInt(node.style.left, 10) || 0;
                            });

                            observer.observe(node, { attributes: true, attributeFilter: ["style"] });
                        });

                        init_connection(instance);
                    });
                }

                const nodesData = Object.entries(data).map(([key, value]) => ({
                    id: key,
                    table: value.table,
                    pos: value.pos
                }));

                const linksData = foreignKeys.map(fk => ({
                    source: fk.father,
                    target: fk.child
                }));

                const simulation = d3.forceSimulation(nodesData)
                    .force("link", d3.forceLink(linksData).id(d => d.id).distance(150))
                    .force("charge", d3.forceManyBody().strength(F))
                    .force("center", d3.forceCenter(800, 500))
                    .on("tick", function(){
                        ticked(data);
                    });

                function ticked(data) {
                    nodesData.forEach(node => {
                        data[node.id].pos.left = node.x;
                        data[node.id].pos.top = node.y;
                    });
                }

                const intervalId = setInterval(() => {
                    render(instance);
                }, 10);

                setTimeout(() => {
                    clearInterval(intervalId);
                }, lockTime);

                render(instance);

                container.addEventListener("click", function () {
                    render(instance);
                });
    </script>
    <script>
        const showAllButton = document.getElementById('show-all-cols');
            let showAll = false;

            showAllButton.addEventListener('click', () => {
                showAll = !showAll;

                if (showAll) {
                    showAllCols();
                } else {
                    hideOtherCols();
                }

                render(instance);
            });

            function showAllCols() {
                Object.entries(data).forEach(([tableKey, table]) => {
                    Object.entries(table.columns).forEach(([columnKey, column]) => {
                        column.show = true;
                    });
                });
            }

            function hideOtherCols() {
                Object.entries(data).forEach(([tableKey, table]) => {
                    Object.entries(table.columns).forEach(([columnKey, column]) => {
                        if (!column.is_fk) {
                            column.show = false;
                        }
                    });
                });
            }
    </script>
    <script>
        $(document).ready(function () {
                const addTableBtn = $('#add-table');
                const tablesInput = $('#tables-input');

                addTableBtn.on('click', function () {
                    $.ajax({
                        url: '/add-ers',
                        type: 'GET',
                        data: { tables: tablesInput.val() },
                        success: function (response) {
                            updateSelectOptions(response);
                        },
                        error: function (xhr) {
                            console.error('?_?', xhr.responseJSON?.error || xhr.statusText);
                        },
                    });
                });
            });
    </script>
</body>

</html>