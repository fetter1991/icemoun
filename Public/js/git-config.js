/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function gitConfig(DOMAIN) {
    var gifArr = [
        {
            'name': '静态女频1', 'index': '6', 'direction': 'right', 'top': '67px', 'left': '', 'right': '55px','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        {
            'name': '动态女频1','type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/6.gif',
            'padding': '19x17',
            'align': 'east', 'margin': '14x10', 'percent': 82, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态女频2','type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/12.gif',
            'padding': '67x57',
            'align': 'east', 'margin': '68x10', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态女频3','type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/16.gif',
            'padding': '74x61',
            'align': 'northeast', 'margin': '54x65', 'percent': 49, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态女频4', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/18.gif',
            'padding': '67x57',
            'align': 'east', 'margin': '68x10', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态女频5', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/19.gif',
            'align': 'west', 'margin': '53x12', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true',
            'padding': '66x59'
        },
        
        {
            'name': '静态电影1', 'index': '7', 'direction': 'left', 'top': '61px', 'left': '55px', 'right': '','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        {
            'name': '静态电影2', 'index': '8', 'direction': 'left', 'top': '61px', 'left': '55px', 'right': '','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        
        

        {
            'name': '动态电影1', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/15.gif',
            'padding': '66x59',
            'align': 'west', 'margin': '68x30', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },

        {
            'name': '动态电影2', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/17.gif',
            'padding': '66x58',
            'align': 'west', 'margin': '68x30', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },

        {
            'name': '动态电影3', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/20.gif',
            'padding': '67x57',
            'align': 'east', 'margin': '68x10', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        
        
        {
            'name': '静态恐怖1', 'index': '9', 'direction': 'right', 'top': '67px', 'left': '', 'right': '55px','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        {
            'name': '静态恐怖2', 'index': '10', 'direction': 'left', 'top': '61px', 'left': '55px', 'right': '','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        {
            'name': '静态恐怖3', 'index': '11', 'direction': 'left', 'top': '61px', 'left': '55px', 'right': '','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        {
            'name': '静态恐怖4', 'index': '12', 'direction': 'left', 'top': '61px', 'left': '55px', 'right': '','code_width':'115','code_height':'115','img_height':'238px', 'type':'png'
        },
        
        {
            'name': '动态恐怖1','type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/13.gif',
            'padding': '67x57',
            'align': 'east', 'margin': '68x10', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态恐怖2', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/7.gif',
            'padding': '19x18',
            'align': 'east', 'margin': '14x10', 'percent': 82, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },

        {
            'name': '动态恐怖3', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/8.gif',
            'padding': '19x18',
            'align': 'east', 'margin': '14x10', 'percent': 82, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态恐怖4', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/9.gif',
            'padding': '19x18',
            'align': 'east', 'margin': '14x10', 'percent': 82, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态恐怖5', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/10.gif',
            'padding': '67x57',
            'align': 'east', 'margin': '68x10', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        },
        {
            'name': '动态恐怖6', 'type':'gif',
            'url': 'https://cdn-yp.' + DOMAIN + '/gifqrcodetmp/gif/14.gif',
            'padding': '67x57',
            'align': 'east', 'margin': '68x10', 'percent': 50, 'opacity': 100, 'repeat': 'false', 'animate': 'true'
        }
    ];
    return gifArr;
}




/**
 * 计算二维码方位和宽高
 * @param {type} direction
 * @param {type} height
 * @param {type} imgH
 * @returns {undefined}
 */
function getCodeStyle(direction,zidinyi) {
    var Padding = {};
    var marOne = '';
    var marTwo = '';
    if (zidinyi != '') {
        var splics = zidinyi.split('x');
        marOne = splics[0];
        marTwo = splics[1];
    }
    switch (direction)
    {
        case 'northwest':
            Padding = {
                top: marOne + 'px',
                left: marTwo + 'px'
            };
            break;
        case 'north':

            Padding = {
                top: marOne + 'px',
                left: marTwo + 'px'
            };

            break;
        case 'northeast':

            Padding = {
                top: marOne + 'px',
                right: marTwo + 'px'
            };
            break;
        case 'west':

            Padding = {
                top: marOne + 'px',
                left: marTwo + 'px'
            };
            break;
        case 'center':
            Padding = {
                top: marOne + 'px',
                left: marTwo + 'px'
            };
            break;
        case 'east':
            Padding = {
                top: marOne + 'px',
                right: marTwo + 'px'
            };
            break;
        case 'southwest':
            Padding = {
                bottom: marOne + 'px',
                left: marTwo + 'px'
            };
            break;
        case 'south':
            Padding = {
                bottom: marOne + 'px',
                left: marTwo + 'px'
            };
            break;
        case 'southeast':
            Padding = {
                bottom: marOne + 'px',
                right: marTwo + 'px'
            };
            break;
        default:
            Padding = {
                top: marOne + 'px',
                left: marTwo + 'px'
            };
    }
    return Padding;
}