<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Model\Image,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\TransformationException,
    Imbo\Exception\StorageException,
    Imbo\Exception\DatabaseException;

/**
 * Image variations generator
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Event\Listeners
 */
class Vector implements ListenerInterface {
    /**
     * Parameters for the event listener
     *
     * @var array
     */
    private $params = [
        // Standard DPI to use for rendering vector images before handing them back to Imbo
        'dpi' => 72,

        // Max DPI to allow images to be used for rendering - this is also used to calculate width/height when an
        // image is stored
        'maxDPI' => 1000,

        // Formats to rasterize
        'formats' => [
            'application/pdf' => true,
        ],

        // Which PDF library to use by default
        'library' => 'xpdf',

        // path to binary files for the chosen library if needed
        'libraryPath' => 'e:/temp/xpdfbin-win-3.04/bin64',

    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     * @throws InvalidArgumentException
     */
    public function __construct($params = []) {
        $params = $params ?: [];

        $this->params = array_replace($this->params, $params);
        $this->finfo = new \finfo(FILEINFO_MIME_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // Generate image variations that can be used in resize operations later on
            //'images.post' => ['generateVariations' => -10],
            // we need to run before the default prepareImage
            'images.post' => ['prepareImage' => 80],

            // Rasterize a vector file
            'storage.image.load' => ['rasterizeImage' => 10],
        ];
    }

    public function prepareImage(EventInterface $event) {
        $request = $event->getRequest();

        // Fetch image data from input
        $imageBlob = $request->getContent();

        if (empty($imageBlob)) {
            $e = new ImageException('No image attached', 400);
            $e->setImboErrorCode(Exception::IMAGE_NO_IMAGE_ATTACHED);

            throw $e;
        }

        $mime = $this->finfo->buffer($imageBlob);

        error_log(serialize($mime));

        if (!$mime || !isset($this->params['formats'][$mime])) {
            return;
        }

        $size = ['width' => null, 'height' => null];

        switch ($this->params['library'])
        {
            case 'xpdf':
                $size = $this->getMetadataXPDF($imageBlob);
                break;

            case 'imagick':
                break;
        }

        if (!$size || !$size['width'] || !$size['height']) {
            return;
        }

        // Store relevant information in the image instance and attach it to the request
        $image = new Image();
        $image->setMimeType($mime)
            ->setExtension(Image::getFileExtension($mime))
            ->setBlob($imageBlob)
            ->setWidth($size['width'])
            ->setHeight($size['height'])
            ->setOriginalChecksum(md5($imageBlob));

        $request->setImage($image);
    }

    private function getMetadataXPDF($blob) {
        $temp = tempnam(sys_get_temp_dir(), 'imbo-vector-pdf-');
        file_put_contents($temp, $blob);

        $pdfinfo = $this->params['libraryPath'] . '/pdfinfo';

        exec($pdfinfo . ' ' . escapeshellarg($temp), $output, $return);
        $info = $this->getOutputListAsKeyValuePairs($output);

        if (!$info || empty($info['Page size'])) {
            return;
        }

        $pageSizes = explode(' ', $info['Page size']);

        if ((count($pageSizes) < 3) || !is_numeric($pageSizes[0]) || !is_numeric($pageSizes[2])) {
            return;
        }

        return ['width' => trim($pageSizes[0]), 'height' => trim($pageSizes[2])];
    }

    private function getOutputListAsKeyValuePairs($list, $separator = ':')
    {
        $info = [];

        foreach ($list as $line)
        {
            $parts = explode($separator, $line);

            if (count($parts) < 2) {
                continue;
            }

            $key = trim(array_shift($parts));
            $value = join(':', $parts);
            $info[$key] = trim($value);
        }

        return $info;
    }

    /**
     * Rasterize a vector image.
     *
     * @param EventInterface $event The current event
     */
    public function rasterizeImage(EventInterface $event) {
        $request = $event->getRequest();
        error_log(serialize($request->getImage()));

        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $vectorData = $event->getStorage()->getImage($publicKey, $imageIdentifier);

        if (empty($this->params['formats'][$this->finfo->buffer($vectorData)]))
        {
            return;
        }


        // Set some data that the storage operations listener usually sets, since that will be
        // skipped since we rasterize a vector image
        //$lastModified = $event->getStorage()->getLastModified($publicKey, $imageIdentifier);
        //$response->setLastModified($lastModified);
        switch ($this->params['library'])
        {
            case 'xpdf':
                $pdftoppm = $this->params['libraryPath'] . '/pdftoppm';
                $source = tempnam(sys_get_temp_dir(), 'imbo-vector-pdf-');
                $destDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('imbo-vector-ppm');

                if (!is_dir($destDir) && !@mkdir($destDir) && !is_dir($destDir))
                {
                    error_log("BARF OUT");
                    return;
                }

                $dest = $destDir . DIRECTORY_SEPARATOR . 'imbo';
                file_put_contents($source, $vectorData);

                exec($pdftoppm . ' -r ' . escapeshellarg($this->params['dpi']) . ' ' . escapeshellarg($source) . ' ' . escapeshellarg($dest));
                $image = new \Imagick($dest . '-000001.ppm');
                break;

            case 'imagick':
                $image = new \Imagick();
                $image->setResolution($this->params['dpi'], $this->params['dpi']);
                $image->readImageBlob($vectorData);
                $image->setImageFormat('RGB');
                $image->setIteratorIndex(0);
                break;
        }

        // Update the model
        $model = $response->getModel();
        $model->setBlob($image->getImageBlob())
            ->setWidth($image->getImageWidth())
            ->setHeight($image->getImageHeight());

        // Set a HTTP header that informs the user agent on which image variation that was used in
        // the transformations
        $response->headers->set('X-Imbo-Vector-DPI', $this->params['dpi']);

        error_log("we deliver!");
        // Stop the propagation of this event
        $event->stopPropagation();
        $event->getManager()->trigger('image.loaded');
    }
}
