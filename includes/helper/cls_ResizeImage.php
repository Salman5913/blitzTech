<?php
/**
 * Resize image class will allow you to resize an image
 *
 * Can resize to exact size
 * Max width size while keep aspect ratio
 * Max height size while keep aspect ratio
 * Automatic while keep aspect ratio
 *
 * <br><br>
 * Usage: <br>
 * $resize = new ResizeImage('images/Be-Original.jpg'); <br>
 * $resize->resizeTo(100, 100); <br>
 * $resize->saveImage('images/be-original-default.jpg'); <br>
 *
 * @link http://www.paulund.co.uk/resize-image-class-php
 */
class ResizeImage
{
    private $ext;
    private $image;
    private $newImage;
    private $origWidth;
    private $origHeight;
    private $resizeWidth;
    private $resizeHeight;

    /**
     * Class constructor requires to send through the image filename
     *
     * @param string $filename - Filename of the image you want to resize
     */
    public function __construct( $filename )
    {
        if(file_exists($filename)) {
            $this->setImage( $filename );
        } else {
            ErrorHandler::setError('Image ' . $filename . ' can not be found, try another image.');
        }
    }

    /**
     * Set the image variable by using image create
     *
     * @param string $filename - The image filename
     */
    private function setImage( $filename )
    {
        $size = getimagesize($filename);
        $this->ext = $size['mime'];

        switch($this->ext)
        {
            // Image is a JPG
            case 'image/jpg':
            case 'image/jpeg':
                // create a jpeg extension
                $this->image = imagecreatefromjpeg($filename);
                break;

            // Image is a GIF
            case 'image/gif':
                $this->image = @imagecreatefromgif($filename);
                break;

            // Image is a PNG
            case 'image/png':
                $this->image = @imagecreatefrompng($filename);
                break;

            // Mime type not found
            default:
                ErrorHandler::setError("File is not an image, please use another file type.");
        }

        $this->origWidth = imagesx($this->image);
        $this->origHeight = imagesy($this->image);
    }

    /**
     * Save the image as the image type the original image was
     *
     * @param $savePath                 - The path to store the new image
     * @param string $imageQuality      - The qulaity level of image to create
     * @param bool $download
     */
    public function saveImage($savePath, $imageQuality="100", $download = false)
    {
        switch($this->ext)
        {
            case 'image/jpg':
            case 'image/jpeg':
                // Check PHP supports this file type
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->newImage, $savePath, $imageQuality);
                }
                break;

            case 'image/gif':
                // Check PHP supports this file type
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->newImage, $savePath);
                }
                break;

            case 'image/png':
                $invertScaleQuality = 9 - round(($imageQuality/100) * 9);

                // Check PHP supports this file type
                if (imagetypes() & IMG_PNG) {
                    imagepng($this->newImage, $savePath, $invertScaleQuality);
                }
                break;
        }

        if($download)
        {
            header('Content-Description: File Transfer');
            header("Content-type: application/octet-stream");
            header("Content-disposition: attachment; filename= ".$savePath."");
            readfile($savePath);
        }

        imagedestroy($this->newImage);
    }

    /**
     * Resize the image to these set dimensions
     *
     * @param  int $width        	- Max width of the image
     * @param  int $height       	- Max height of the image
     * @param  string $resizeOption - Scale option for the image
     */
    public function resizeTo( $width, $height, $resizeOption = 'default' )
    {
        switch(strtolower($resizeOption))
        {
            case 'exact':
                $this->resizeWidth = $width;
                $this->resizeHeight = $height;
                break;

            case 'maxwidth':
                $this->resizeWidth  = $width;
                $this->resizeHeight = $this->resizeHeightByWidth($width);
                break;

            case 'maxheight':
                $this->resizeWidth  = $this->resizeWidthByHeight($height);
                $this->resizeHeight = $height;
                break;

            default:
                if($this->origWidth > $width || $this->origHeight > $height)
                {
                    $this->resizeWidth = $width;
                    $this->resizeHeight = $height;

                    if ( $this->origWidth > $this->origHeight ) {
                        $this->resizeHeight = $this->resizeHeightByWidth($width);
                        $this->resizeWidth  = $width;
                    } else if( $this->origWidth < $this->origHeight ) {
                        $this->resizeWidth  = $this->resizeWidthByHeight($height);
                        $this->resizeHeight = $height;
                    }
                } else {
                    $this->resizeWidth = $width;
                    $this->resizeHeight = $height;
                }
                break;
        }

        $this->newImage = imagecreatetruecolor($this->resizeWidth, $this->resizeHeight);
        imagealphablending( $this->newImage, false );
        imagesavealpha( $this->newImage, true );

        imagecopyresampled($this->newImage, $this->image, 0, 0, 0, 0, $this->resizeWidth, $this->resizeHeight, $this->origWidth, $this->origHeight);
    }

    /**
     * Get the resized height from the width keeping the aspect ratio
     *
     * @param $width int - Max image width
     * @return float - Height keeping aspect ratio
     */
    private function resizeHeightByWidth($width)
    {
        return floor(($this->origHeight/$this->origWidth)*$width);
    }


    /**
     * Get the resized width from the height keeping the aspect ratio
     *
     * @param $height int - Max image height
     * @return float Width keeping aspect ratio
     */
    private function resizeWidthByHeight($height)
    {
        return floor(($this->origWidth/$this->origHeight)*$height);
    }


    static function getExtension($FileName)
    {
        $Ext = explode(".", $FileName);
        return strtolower($Ext[count($Ext)-1]);
    }
}
