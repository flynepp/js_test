// JavaScript Document
var cav = document.getElementById("cav").getContext("2d");

/**
 * 获取一个随机数，取值范围[0,n]
 * 
 * @param {number} n - 生成的随机数的上限
 * @returns {number} - 生成的随机数
 */
function RandomNumber(n) {
    return Math.round(Math.random() * n);
}

/**
 * 获取一个随机数，取值范围[m, n]
 * n > m
 * 
 * @param {number} m - 范围的下限
 * @param {number} n - 范围的上限
 * @returns {number} - 生成的随机数
 */
function RandomNumberBetween(m, n) {
    return m + Math.round(Math.random() * (n - m));
}

/**
 * 画圆
 * 
 * @param {number} x - 圆心的横坐标
 * @param {number} y - 圆心的纵坐标
 * @param {number} r - 圆的半径
 * @param {string} c - 圆的填充颜色
 */
function oneCircle(x, y, r, c) {
    cav.beginPath();          // 开始一个新的路径
    cav.fillStyle = c;        // 设置填充颜色
    cav.arc(x, y, r, 0, Math.PI * 2, true); // 绘制圆形
    cav.fill();               // 填充圆形
}

/**
 * 获取随机位置在圆周上
 *
 * @param {number[]} p - 坐标数组
 * @param {number} r - 圆的半径
 * @returns {number[]} - 新的坐标数组
 */
function getRandomPos(p, r) {
    let angle = Math.random() * 2 * Math.PI; // 生成一个随机角度
    return [
        p[0] + Math.round(r * Math.sin(angle)),
        p[1] + Math.round(r * Math.cos(angle))
    ];
}

/**
 * 计算两点坐标的距离
 * 
 * @param {number[]} x - 第一个点的坐标 [x, y]
 * @param {number[]} y - 第二个点的坐标 [x, y]
 * @returns {number} - 两点之间的距离
 */
function getInstant(x, y) {
    let m = x[0] - y[0];  // x 轴方向的距离
    let n = x[1] - y[1];  // y 轴方向的距离
    return Math.round(Math.sqrt(m * m + n * n)); // 计算并返回距离
}

function Circles() {
    let position = []; // 用于存储圆的位置

    return {

        /**
         * 画多个圆
         * 
         * @param {number|boolean} distance - 圆与圆之间的最小距离（px），或者 false
         * @param {number[]|number} count - 圆的数量（默认为150~200），也可以是一个具体的数值
         */
        getCircle: function (distance = false, count = [150, 200]) {
            let times;
        
            if (Array.isArray(count)) {
                times = RandomNumberBetween(count[0], count[1]); // 生成圆的数量
            } else {
                times = count; // 使用指定的数量
            }
        
            position = []; // 清空位置数组
        
            for (let i = 0; i < times; i++) {
                let x = RandomNumber(1600); // 生成随机 x 坐标
                let y = RandomNumber(900);  // 生成随机 y 坐标
                let r = RandomNumberBetween(2, 3); // 生成随机半径
                let c = 'rgba(25,0,25,0.5)'; // 设置圆的颜色
        
                if (distance) {
                    let isValidPosition = true; // 标记位置是否有效
        
                    for (let j = 0; j < position.length; j++) {
                        let existingPoint = position[j]; // 已有圆的位置
                        let distanceBetweenPoints = getInstant([x, y], existingPoint); // 计算距离
        
                        if (distanceBetweenPoints < distance) { // 如果距离小于要求的最小距离
                            isValidPosition = false; // 标记位置无效
                            break; // 跳出循环
                        }
                    }
                    if (!isValidPosition) {
                        i--; // 重新生成位置
                        continue; // 继续下一次循环
                    }
                }
        
                oneCircle(x, y, r, c); // 画圆
                position.push([x, y]); // 将圆的位置存储到数组
            }
        },

        /**
         * 获取所有画的圆的位置
         * 
         * @returns {number[][]} - 返回圆的位置数组
         */
        getPos: function() {
            return position; // 返回位置数组
        }    
    }
}

/**
 * 画线
 * 
 * @param {number[]} x - 起点坐标 [x, y]
 * @param {number[]} y - 终点坐标 [x, y]
 * @param {string} c - 线条颜色（例如 'rgba(255,0,0,1)'）
 */
function getLine(x, y, c) {
    cav.strokeStyle = c; // 设置线条颜色
    cav.beginPath();    // 开始一个新的路径
    cav.moveTo(x[0], x[1]); // 移动到起点
    cav.lineTo(y[0], y[1]); // 画线到终点
    cav.stroke();       // 描边线条
}

function I_need_cirlce_and_line() {
    const circle = Circles(); // 创建 Circles 实例
    circle.getCircle(50, 30); // 画 15 个圆，确保圆与圆之间的最小距离为 50 px

    let position = [];
    let pos = circle.getPos(); // 获取所有圆的位置
    
    /**
     * 处理每个圆的坐标并生成关联坐标
     * 位置数组（position）:
     * [
     *   [
     *     [x1, y1], // 第一个圆的原始坐标
     *     [x1_new1, y1_new1], // 生成的第一个新坐标
     *     [x1_new2, y1_new2]  // 生成的第二个新坐标（如果有的话）
     *   ],
     *   [
     *     [x2, y2], // 第二个圆的原始坐标
     *     [x2_new1, y2_new1], // 生成的第一个新坐标
     *     [x2_new2, y2_new2]  // 生成的第二个新坐标（如果有的话）
     *   ]
     * ]
     */
    for(let i = 0; i < pos.length; i++) {
        position[i] = []; // 初始化位置数组
        position[i].push(pos[i]); // 添加圆的原始坐标

        let nnmn = RandomNumberBetween(1, 2);
        for(let j = 0; j < nnmn; j++) {
            let newPos = getRandomPos(position[i][0], RandomNumberBetween(40, 60)); // 生成新坐标
            oneCircle(
                newPos[0],
                newPos[1],
                RandomNumberBetween(2, 3),
                'rgba(25,0,25,0.5)',
            );

            position[i].push(newPos); // 将新坐标添加到位置数组

            getLine(position[i][j], newPos, 'rgba(25,0,25,0.5)'); // 绘制从圆到新坐标的连线
        }
    }
}