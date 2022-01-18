<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Clip transformation for making an image transparent outside of a clipping mask
 */
class Clip extends Transformation
{
    public function transform(array $params)
    {
        $pathName = null;

        if (!empty($params['path'])) {
            $pathName = $params['path'];

            $metadata = $this->event->getDatabase()->getMetadata(
                $this->image->getUser(),
                $this->image->getImageIdentifier(),
            );

            if (empty($metadata['paths']) || !is_array($metadata['paths']) || !in_array($pathName, $metadata['paths'])) {
                if (isset($params['ignoreUnknownPath'])) {
                    return;
                }

                throw new InvalidArgumentException(
                    'Selected clipping path "' . $pathName . '" was not found in the image. Add the ignoreUnknownPath argument if you want to ignore this error.',
                    Response::HTTP_BAD_REQUEST,
                );
            }
        }

        $currentAlphaChannelMode = $this->imagick->getImageAlphaChannel();

        try {
            $this->imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_TRANSPARENT);

            // since the implementation of clipImage in ImageMagick is
            //     return(ClipImagePath(image,"#1",MagickTrue,exception));
            // .. this should be the same by setting inside=true.
            if ($pathName) {
                $this->imagick->clipImagePath($pathName, true);
            } else {
                $this->imagick->clipImage();
            }

            $this->imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
        } catch (ImagickException $e) {
            // NoClipPathDefined - the image doesn't have a clipping path, but this isn't a fatal error.
            if ($e->getCode() == 410) {
                // but we need to reset the alpha channel mode in case someone else is doing something with it
                if ($currentAlphaChannelMode) {
                    $this->imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                }

                return;
            }

            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
