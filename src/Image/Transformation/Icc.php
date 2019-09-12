<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\ConfigurationException,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Transformation for applying ICC profiles to an image.
 *
 * The transformation is not enabled by default, but can be added to the list of transformations in
 * your custom configuration. The transformation requires a list of key => .icc-file pairs, and
 * exposes these profiles through the `profile` parameter given for the transformation.
 *
 * The `default` key in the array is used if no profile is given when
 * the transformation is invoked.
 *
 *      'transformations' => [
 *          'icc' => function () {
 *              return new Imbo\Image\Transformation\Icc([
 *                  'default' => '/path/to/imbo/data/profiles/sRGB_v4_ICC_preference.icc',
 *                  'srgb' => '/path/to/imbo/data/profiles/sRGB_v4_ICC_preference.icc',
 *              ]);
 *          },
 *      ],
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\Transformations
 */
class Icc extends Transformation {
    /**
     * @var array
     */
    protected $profiles;

    /**
     * Class constructor
     *
     * @param array $profiles An associative array where the keys are profile names that can be used
     *                        with the `profile` parameter for the transformation, and the values
     *                        are paths to the profiles themselves.
     */
    public function __construct($profiles) {
        if (!is_array($profiles)) {
            throw new ConfigurationException(get_class() . ' requires an array with name => profile file (.icc) mappings when created.', 500);
        }

        $this->profiles = $profiles;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        if (empty($params['profile']) && empty($this->profiles['default'])) {
            throw new InvalidArgumentException('No profile name given for which ICC profile to use and no profile is assigned to the "default" name.', 400);
        } else if (!empty($params['profile']) && empty($this->profiles[$params['profile']])) {
            throw new InvalidArgumentException('The given ICC profile name ("' . $params['profile'] . '") is unknown to the server.', 400);
        }

        $file = empty($params['profile']) ? $this->profiles['default'] : $this->profiles[$params['profile']];

        if (!file_exists($file)) {
            throw new ConfigurationException('Could not load ICC profile referenced by "' . (!empty($params['profile']) ? $params['profile'] : 'default') . '": ' . $file, 500);
        }

        $iccProfile = file_get_contents($file);

        try {
            $this->imagick->profileImage('icc', $iccProfile);
        } catch (ImagickException $e) {
            // Detect if there's a mismatch between the embedded profile and the color space in the image
            if ($e->getCode() == 465) {
                try {
                    // strip the existing profile, relying in color space to be correct
                    $this->imagick->profileImage('*', null);

                    // try to apply the profile again
                    $this->imagick->profileImage('icc', $iccProfile);
                } catch (ImagickException $e) {
                    throw new TransformationException($e->getMessage(), 400, $e);
                }
            } else {
                throw new TransformationException($e->getMessage(), 400, $e);
            }
        }

        $this->image->hasBeenTransformed(true);
    }
}
