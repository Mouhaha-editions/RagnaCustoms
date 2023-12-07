<?php


namespace App\Command;


use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class OptimizationCommand extends Command
{
    protected static $defaultName = 'opti:covers';
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;

        return parent::__construct();
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cdir = scandir($this->kernel->getProjectDir()."/public/covers");
        foreach ($cdir as $key => $value) {
            if ($value == "." || $value == "..") {
                continue;
            }
            try {
                $filedir = $this->kernel->getProjectDir()."/public/covers/".$value;
                $image = Image::make($filedir);
                $background = Image::canvas(349, 349, 'rgba(255, 255, 255, 0)');
                if ($image->width() >= $image->height()) {
                    $image->widen(349);
                } else {
                    $image->heighten(349);
                }
                $background->insert($image, 'center-center');
                $background->save($filedir);

                $cover = explode('.', $value);

                if (in_array(strtolower($cover[1]), [
                    'jpg',
                    'jpeg',
                ])) {
                    $image = imagecreatefromjpeg($filedir);
                    imagewebp($image, $this->kernel->getProjectDir()."/public/covers/".$cover[0].".webp", 100);
                    unlink($filedir);
                    imagedestroy($image);
                } elseif (in_array(strtolower($cover[1]), ['gif'])) {
                    $image = imagecreatefromgif($filedir);
                    imagewebp($image, $this->kernel->getProjectDir()."/public/covers/".$cover[0].".webp", 100);
                    unlink($filedir);
                    imagedestroy($image);
                } elseif (in_array(strtolower($cover[1]), ['png'])) {
                    $image = imagecreatefrompng($filedir);
                    imagewebp($image, $this->kernel->getProjectDir()."/public/covers/".$cover[0].".webp", 100);
                    unlink($filedir);
                    imagedestroy($image);
                }


            } catch (Exception $exception) {
                echo $filedir." ".$exception->getMessage();
            }
        }

        return Command::SUCCESS;
    }
}
