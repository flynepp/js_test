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
 * @param {number[]} x 坐标数组    x= [a, b]    
 * @param {number[]} y 坐标数组    y= [m, n]
 * @returns {number}               
 */
function getInstant(x, y) {
    let m = x[0] - y[0];
    let n = x[1] - y[1];
    return Math.round(Math.sqrt(m * m + n * n));
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
}

function I_need_cirlce_and_line() {
    const circle = Circles();
    circle.getCircle(50,15);//draw circles, distance > 50, count = 15

    let position = [];
    let pos = circle.getPos();
    
    /**
     * 处理每个圆的坐标并生成关联坐标
     * 位置数组（position）:
     * [
     *   // 每个元素是一个数组，代表一个圆的位置及其关联的坐标
     *   [
     *     // position[0] 是第一个圆的坐标及其关联坐标
     *     [x1, y1], // 第一个圆的原始坐标
     *     [x1_new1, y1_new1], // 生成的第一个新坐标
     *     [x1_new2, y1_new2]  // 生成的第二个新坐标（如果有的话）
     *   ],
     *   [
     *     // position[1] 是第二个圆的坐标及其关联坐标
     *     [x2, y2], // 第二个圆的原始坐标
     *     [x2_new1, y2_new1], // 生成的第一个新坐标
     *     [x2_new2, y2_new2]  // 生成的第二个新坐标（如果有的话）
     *   ]
     *   // 依此类推，直到所有圆的位置和关联坐标都被处理
     * ]
     */
    for(let i = 0; i < pos.length; i++) {
        position[i] = [];
        position[i].push(pos[i][0],pos[i][1]); 

        for(let j = 0; j < RandomNumberBetween(1, 2); j++) {
            let newPos = getRandomPos(position[i][0],RandomNumberBetween(20, 30));
            position[i].push(newPos);
        }
    }
} 
