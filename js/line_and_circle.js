// JavaScript Document
var cav = document.getElementById("cav").getContext("2d");


/**
 * 获取一个随机数，取值范围[0,n]
 * 
 * @param {number} n 
 * @returns {number}
 */
function RandomNumber(n) {
    return Math.round(Math.random() * n);
}

/**
 * 获取一个随机数，取值范围[m, n]
 * n > m
 * 
 * @param {number} m 
 * @param {number} n 
 * @returns {number}
 */
function RandomNumberBetween(m, n) {
    return m + Math.round(Math.random() * (n - m));
}

/**
 * 画圆
 * 
 * @param {number} x 横坐标
 * @param {number} y 纵坐标
 * @param {number} r 半径
 * @param {string} c 颜色
 */
function circle(x, y, r, c) {
    cav.beginPath();
    cav.fillStyle = c;
    cav.arc(x, y, r, 0, Math.PI * 2, true);
    cav.fill();
}

/**
 * 计算两点坐标的距离
 * 
 * @param {number[]} x 坐标数组    x= [a, b]    
 * @param {number[]} y 坐标数组    y= [m, n]
 * @returns {number}               
 */
function getInstant(x, y) {
    let m = x[0] - y[0];
    let n = x[1] - y[1];
    return Math.round(Math.sqrt(m * m + n * n));
}

/**
 * 画多个圆
 * 
 * @param {number|boolean} distance 间距（px）
 * @param {number[]|number} count 数量（默认为150~200），也可填入单个数值
 */
function getCircle(distance = false, count = [150, 200]) {
    let times;

    if (Array.isArray(count)) {
        times = RandomNumberBetween(count[0], count[1]);
    } else {
        times = count;
    }

    let position = [];

    for (let i = 0; i < times; i++) {
        let x = RandomNumber(1600);
        let y = RandomNumber(900);
        let r = RandomNumberBetween(3, 5);
        let c = 'rgba(25,0,25,0.5)';

        if (distance) {
            let isValidPosition = true;

            for (let j = 0; j < position.length; j++) {
                let existingPoint = position[j];
                let distanceBetweenPoints = getInstant([x, y], existingPoint);

                if (distanceBetweenPoints < distance) {
                    isValidPosition = false;
                    break;
                }
            }

            if (!isValidPosition) {
                i--;
                continue;
            }
        }

        circle(x, y, r, c);
        position.push([x, y]);
    }
}
