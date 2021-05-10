<?php


namespace App\Helper;


class AIMapper
{
    private $file;
    private $duration;
    /**
     * @var float|int
     */
    private $height;
    private $bpm;
    /**
     * @var float|int
     */
    private $notesGap;
    private $ratio;
    private $level;


    public function __construct(string $file, int $duration, int $bpm, $ratio, $level)
    {
        $this->file = $file;
        $this->duration = $duration;
        $this->bpm = $bpm;
        $this->ratio = $ratio;
        $this->level = $level;
        $this->notesGap = $bpm / 60 * $duration;
    }

    public function read()
    {
        $image = imagecreatefrompng($this->file);
        list($width, $height, $type, $attr) = getimagesize($this->file);
        $this->height = $height / 2;
        $detail = [];
        $count = [];
        $tempDetail = [];
        $reference = imagecolorat($image, 0, 0);
        for ($i = 0; $i < $width; $i++) {
            if (!isset($detail[$i])) {
                $detail[$i] = [];
                $count[$i] = 0;
            }
            for ($j = 0; $j < $height / 2; $j++) {
                $color = imagecolorat($image, $i, $j);
                $detail[$i][$j] = !($reference == $color);
                if ($reference != $color) {
                    $count[$i] += 1;
                }
            }
            if ($count[$i] == 0) {
                unset($detail[$i]);
            }
        }

        return [
            $detail,
            $count
        ];

    }

    public function map(array $detail, $outFile)
    {
        $map = [
            "_version" => "2.2.0",
            "_customData" => [
                "_time" => $this->duration,
                "_BPMChanges" => []
            ],
            "_bookmarks" => [],
            "_events" => [],
            '_notes' => []
        ];
        $times = [];
        $lastColumn = 0;
        $i = 0;
        $ratio = $this->ratio;
        $lastTime = 0;
        $lastNote = 0;
        foreach ($detail[0] as $sec => $column) {
            if ($lastColumn == $detail[1][$sec]) {
                continue;
            }

            if ($detail[1][$sec] > $lastColumn + 15) {
                if ($lastColumn != 0) {
                    $calculatedTime = $sec / $ratio;
                    if ($lastTime + (2.5 / $this->level) <= $calculatedTime) {
                        $lastTime = $calculatedTime;

                        if ($this->level <= 4) {
                            if ($lastColumn - $lastNote >= 10) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            } else {
                                if ($i % 2) {
                                    $map["_notes"][] = [
                                        "_time" => $calculatedTime,
                                        "_lineIndex" => rand(0, 1),
                                        "_lineLayer" => 1,
                                        "_type" => 0,
                                        "_cutDirection" => 1,
                                    ];
                                } else {
                                    $map["_notes"][] = [
                                        "_time" => $calculatedTime,
                                        "_lineIndex" => rand(2, 3),
                                        "_lineLayer" => 1,
                                        "_type" => 0,
                                        "_cutDirection" => 1,
                                    ];
                                }
                            }
                        } elseif($this->level <= 7) {
                            $rand = rand(0, 100);
                            if ($rand <= 80) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => rand(0, 3),
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 83) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 1,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 2,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 85) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 88) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 2,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 91) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 1,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 95) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 1,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif($rand <= 100) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 2,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];

                            }
                        }else {
                            $rand = rand(0, 100);
                            if ($rand <= 46) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => rand(0, 3),
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];

                            }elseif ($rand <= 55) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 1,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 2,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 64) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 73) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 2,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 82) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 1,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 91) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 0,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 1,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }elseif ($rand <= 100) {
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 2,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                                $map["_notes"][] = [
                                    "_time" => $calculatedTime,
                                    "_lineIndex" => 3,
                                    "_lineLayer" => 1,
                                    "_type" => 0,
                                    "_cutDirection" => 1,
                                ];
                            }
                        }
                        $i++;
                    }
                }
                $lastNote = $detail[1][$sec];
            }
            $lastColumn = $detail[1][$sec];

        }
        file_put_contents($outFile, json_encode($map));

    }

}