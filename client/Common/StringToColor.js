/*!
* Name: string_to_color
* Author: Brandon Corbin [code@icorbin.com]
* Website: http://icorbin.com
*/
module.exports = function (str) {
    // Generate a Hash for the String
    let hash = function(word) {
        let h = 0;
        for (let i = 0; i < word.length; i++) {
            h = word.charCodeAt(i) + ((h << 5) - h);
        }
        return h;
    };

    // Change the darkness or lightness
    let shade = function(color, prc) {
        let num = parseInt(color, 16),
            amt = Math.round(2.55 * prc),
            R = (num >> 16) + amt,
            G = (num >> 8 & 0x00FF) + amt,
            B = (num & 0x0000FF) + amt;
        return (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255))
            .toString(16)
            .slice(1);
    };

    let pastel = (int)=> {
        const index = int % 32 / 32; // 32 colors only
        return "hsl(" + 360 * index + ',' +
            (45 + 50 * index) + '%,' +
            (75 + 10 * index) + '%)'
    };

    // Convert init to an RGBA
    let int_to_rgba = function(i) {
        return ((i >> 24) & 0xFF).toString(16) +
            ((i >> 16) & 0xFF).toString(16) +
            ((i >> 8) & 0xFF).toString(16) +
            (i & 0xFF).toString(16);
    };

    //return shade(int_to_rgba(hash(str)), -10);
    return pastel(hash(str));
};
