const { Jimp } = require('jimp');

async function checkImage() {
    try {
        const image = await Jimp.read('public/images/komdigi-logo.png');
        
        // Use autocrop to remove transparent borders
        image.autocrop();
        await image.write('public/images/komdigi-logo.png');
        console.log(`Autocropped image to width: ${image.bitmap.width}, height: ${image.bitmap.height}`);
    } catch (err) {
        console.error(err);
    }
}

checkImage();
