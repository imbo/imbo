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

use Imbo\Exception\TransformationException,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
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
class AutoRotate extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.autorotate' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');

        try {
            // Get orientation from exif data
            $orientation = $this->imagick->getImageOrientation();

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
                    $this->imagick->rotateImage('#000', $rotate);

                    /**
                     * Recalculate width and height if number of degrees are not
                     * dividable by 180, meaning height and width is changed.
                     */
                    if ($rotate % 180) {
                        $size = $this->imagick->getImageGeometry();

                        $image->setWidth($size['width'])
                              ->setHeight($size['height']);
                    }
                }

                if ($flipHorizontally) {
                    $this->imagick->flopImage();
                }

                if ($flipVertically) {
                    $this->imagick->flipImage();
                }

                if ($rotate || $flipHorizontally || $flipVertically) {
                    // Set the image orientation so it reflects the transformation that's been done
                    $this->imagick->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
                    $image->hasBeenTransformed(true);
                }
            }
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
