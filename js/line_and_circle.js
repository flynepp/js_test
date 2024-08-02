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
 * 获取随机位置在圆周上
 *
 * @param {number[]} p 坐标数组
 * @param {number} r 半径
 * @returns {number[]} 新的坐标数组
 */
function getRandomPos(p, r) {
    let angle = Math.random() * 2 * Math.PI;
    p[0] = Math.round(r * Math.sin(angle));
    p[1] = Math.round(r * Math.cos(angle));
    return p;
}

/**
 * 计算两点坐标的距离
 * 
 * @param {number[]} x 坐标数组
 * @param {number[]} y 坐标数组
 * @returns {number}
 */
function getInstant(x, y) {
    let m = x[0] - y[0];
    let n = x[1] - y[1];
    let r = Math.round(Math.sqrt(m * m + n * n));
    return r;
}

function Circles(){
    let position = [];

    return {
    /**
     * 画多个圆
     * 
     * @param {number|boolean} distance 间距（px）
     * @param {number[]|number} count 数量（默认为150~200），也可填入单个数值
     */
    getCircle: function (distance = false, count = [150, 200]) {
        let times;
    
        if (Array.isArray(count)) {
            times = RandomNumberBetween(count[0], count[1]);
        } else {
            times = count;
        }
    
        position = [];
    
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
    },

    /**
     * 获取所有画的圆的位置
     * 
     * @returns {number[][]} 返回位置数组
     */
    getPos: function() {
        return position;
    }    
}

function I_need_cirlce_and_line() {
    getCircle(50,15);//distance > 50, count = 15

    
}
