<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Model\Image,
    Imbo\Exception\TransformationException,
    Imagick,
    ImagickException,
    ImagickPixelException;

/**
 * Automatic rotate transformation. Rotates and flips the image
 * based on the EXIF orientation tag.
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Image\Transformations
 */
class AutoRotate extends Transformation implements TransformationInterface {
    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            // Get orientation from exif data
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());
            $orientation = $imagick->getImageOrientation();

            /**
             * Transform image if orientation is set and greater than 1
             * (Imagick::ORIENTATION_TOPLEFT)
             */
            if ($orientation > 1) {
                $flipHorizontally = false;
                $flipVertically = false;
                $rotate = 0;

                switch ($orientation) {
                    case Imagick::ORIENTATION_TOPRIGHT:     //2
                        $flipHorizontally = true;
                        break;
                    case Imagick::ORIENTATION_BOTTOMRIGHT:  //3
                        $rotate = 180;
                        break;
                    case Imagick::ORIENTATION_BOTTOMLEFT:   //4
                        $flipVertically = true;
                        break;
                    case Imagick::ORIENTATION_LEFTTOP:      //5
                        $rotate = 90;
                        $flipHorizontally = true;
                        break;
                    case Imagick::ORIENTATION_RIGHTTOP:     //6
                        $rotate = 90;
                        break;
                    case Imagick::ORIENTATION_RIGHTBOTTOM:  //7
                        $rotate = 90;
                        $flipVertically = true;
                        break;
                    case Imagick::ORIENTATION_LEFTBOTTOM:   //8
                        $rotate = 270;
                        break;
                }

                if ($rotate) {
                    $imagick->rotateImage('#000', $rotate);

                    /**
                     * Recalculate width and height if number of degrees are not
                     * dividable by 180, meaning height and width is changed.
                     */
                    if ($rotate % 180) {
                        $size = $imagick->getImageGeometry();

                        $image->setWidth($size['width'])
                              ->setHeight($size['height']);
                    }
                }

                if ($flipHorizontally) {
                    $imagick->flopImage();
                }

                if ($flipVertically) {
                    $imagick->flipImage();
                }
            }

            // Set the image orientation so it reflects the transformation that's been done
            $imagick->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            $image->setBlob($imagick->getImageBlob());
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
