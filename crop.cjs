const { Jimp } = require('jimp');

async function checkImage() {
    try {
        const image = await Jimp.read('public/images/komdigi-logo.png');
        console.log(`Image width: ${image.bitmap.width}, height: ${image.bitmap.height}`);
        
        // Since the text is usually at the bottom 30-40% of the image, we can crop out the bottom part.
        // We'll calculate the new height.
        // Or better yet, we can crop from y=0 to y=height*0.65 or something.
        // Wait, Jimp version 1.0+ has different syntax maybe.
        // Let's just crop 25% from the bottom.
        const cropHeight = Math.floor(image.bitmap.height * 0.7);
        image.crop({ x: 0, y: 0, w: image.bitmap.width, h: cropHeight });
        await image.write('public/images/komdigi-logo.png');
        console.log(`Cropped image to height: ${cropHeight}`);
    } catch (err) {
        console.error(err);
    }
}

checkImage();
