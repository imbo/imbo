<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\ConfigurationException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(Icc::class)]
class IccTest extends TestCase
{
    public function testExceptionWithoutProfiles(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'No profile name given for which ICC profile to use and no profile is assigned to the "default" name.',
            Response::HTTP_BAD_REQUEST,
        ));

        (new Icc([]))->transform([]);
    }

    public function testExceptionWithInvalidName(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'The given ICC profile name ("foo") is unknown to the server.',
            Response::HTTP_BAD_REQUEST,
        ));

        (new Icc([]))->transform(['profile' => 'foo']);
    }

    public function testTransformationHappensWithMatchingName(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $profilePath = DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('profileImage')
            ->with('icc', file_get_contents($profilePath));

        (new Icc([
            'foo' => $profilePath,
        ]))
            ->setImagick($imagick)
            ->setImage($image)
            ->transform(['profile' => 'foo']);
    }

    public function testTransformationHappensWithDefaultKey(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $profilePath = DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('profileImage')
            ->with('icc', file_get_contents($profilePath));

        (new Icc([
            'default' => DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc',
        ]))
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([]);
    }

    public function testThrowsExceptionWhenImagickFailsWithAFatalError(): void
    {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('profileImage')
            ->willThrowException($e = new ImagickException('Some error'));

        $this->expectExceptionObject(new TransformationException('Some error', Response::HTTP_BAD_REQUEST, $e));

        (new Icc([
            'default' => DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc',
        ]))
            ->setImagick($imagick)
            ->transform([]);
    }

    public function testThrowsExceptionWhenInvalidPathIsUsed(): void
    {
        $path = DATA_DIR.'/foo/bar.icc';

        $this->expectExceptionObject(new ConfigurationException(
            sprintf(
                'Could not load ICC profile referenced by "default": %s',
                $path,
            ),
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));

        (new Icc(['default' => $path]))->transform([]);
    }

    public function testStripProfileOnMismatch(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $expectedProfile = file_get_contents(DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc');

        $imagick = $this->createStub(Imagick::class);
        $imagick
            ->method('profileImage')
            ->willReturnCallback(
                static function (string $name, string $profile) use ($expectedProfile): bool {
                    /** @var int */
                    static $i = 0;

                    return match ([$i++, $name, $profile]) {
                        [0, 'icc', $expectedProfile] => throw new ImagickException('error #1', 465),
                        [1, '*', ''] => true,
                        [2, 'icc', $expectedProfile] => true,
                    };
                },
            );

        (new Icc([
            'default' => DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc',
        ]))
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([]);
    }

    public function testThrowsExceptionWhenApplyingStrippedProfileFails(): void
    {
        $expectedProfile = file_get_contents(DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc');
        $e = new ImagickException('error #2');

        $imagick = $this->createStub(Imagick::class);
        $imagick
            ->method('profileImage')
            ->willReturnCallback(
                static function (string $name, string $profile) use ($expectedProfile, $e): bool {
                    /** @var int */
                    static $i = 0;

                    return match ([$i++, $name, $profile]) {
                        [0, 'icc', $expectedProfile] => throw new ImagickException('error #1', 465),
                        [1, '*', ''] => true,
                        [2, 'icc', $expectedProfile] => throw $e,
                    };
                },
            );

        $this->expectExceptionObject(new TransformationException('error #2', Response::HTTP_BAD_REQUEST, $e));

        (new Icc([
            'default' => DATA_DIR.'/profiles/sRGB_v4_ICC_preference.icc',
        ]))
            ->setImagick($imagick)
            ->setImage($this->createStub(Image::class))
            ->transform([]);
    }
}
