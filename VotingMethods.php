<?php

/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 26.04.2018
 * Time: 0:22
 */
class VotingMethods
{
    /**
     * Профиль данных
     * @var array
     */
    private $data = [];
    private $profile = [];
    /**
     * Массив кандидатов
     * @var array
     */
    private $candidatesArray = [];
    /**
     * Коллективное ранжирование для абсолютного большинства
     * @var array
     */
    private $absoluteMajorityRank = [];

    /**
     * Коллективное ранжирование для метода последовательного исключения
     * @var array
     */
    private $consecutiveExclusionRank = [];

    function __construct(array  $data)
    {
        $this->data = $data;
        $this->profile = $data;
        $this->createArrayCandidates();
    }

    /**
     * Обновление профиля данных
     */
    private function updateData():void
    {
        $this->data = $this->profile;
        return;
    }

    /**
     * Создание массива кандидатов
     */
    private function createArrayCandidates():void
    {
        unset($this->candidatesArray);
        foreach ($this->data[0] as $candidates) {
            foreach ($candidates as $candidate) {
                $this->candidatesArray[$candidate] = 0;
            }
        }
        return;
    }

    /**
     * Получение суммы голосов для кандидатов, занявших первые места в индивидуальных предпочтениях избирателей
     * и их сортировка в порядке убывания голосов
     * @var array
     */
    private function getAmountOfVotes(array $data):void
    {
        for ($i = 0; $i < count($this->data); $i++) {
            foreach ($data[$i] as $num => $candidates) {
                $this->candidatesArray[$candidates[0]] += $num;
            }
        }
        //Сортируем кандидатов в порядке убывания
        arsort($this->candidatesArray);
        return;
    }

    /**
     * Метод абсолютного большинства в 2 тура
     * @return array
     */
    public function absoluteMajority():array
    {
        $data = $this->data;
        //Получаем сумму голосов для кандидатов, занявших первые места в индивидуальных предпочтениях избирателей
        $this->getAmountOfVotes($data);
        //Обрезаем - оставляем 2 кандидатов с наибольшим кол-вом голосов
        $deleteCandidates = array_splice($this->candidatesArray, 2);
        //Получаем кандидатов, которые выбыли на 1 этапе
        $keys = array_keys($deleteCandidates);

        //Удалим выбывших кандидатов с профиля голосования
        for ($i = 0; $i < count($data); $i++) {
            foreach ($data[$i] as &$candidates) {
                $count = count($candidates);
                for ($j = 0; $j < $count; $j++) {
                    if (in_array($candidates[$j], $keys)) {
                        unset($candidates[$j]);
                    }
                }
                //Упорядочим по ключам(0,1,2...)
                $candidates = array_values($candidates);
            }
        }
        unset($candidates);

        //Создадим массив кандидатов заново
        $this->createArrayCandidates();
        //2  тур голосования: выбираем лучшего с двух оставшихся

        //Получаем сумму голосов для кандидатов, занявших первые места в индивидуальных предпочтениях избирателей
        $this->getAmountOfVotes($data);
        //Получаем кандидата-победителя во 2 туре
        $winner = key($this->candidatesArray);
        //Добавляем победтеля в массив коллективной ранжировки
        array_push($this->absoluteMajorityRank, $winner);

        //Удаляем с данных кандидата-победителя
        for ($i = 0; $i < count($this->data); $i++) {
            foreach ($this->data[$i] as &$candidates) {
                foreach ($candidates as $num => $candidate) {
                    if ($candidate == $winner)
                        unset($candidates[$num]);
                }
                $candidates = array_values($candidates);
            }
        }
        unset($candidates);
        //Переходим к определению след. кандидата-победителя
        foreach ($this->data[0] as $candidates) {
            //Если кол-во кандидатов > 1
            if (count($candidates) > 1) {
                //Создаем массив кандидатов
                $this->createArrayCandidates();
                //Начинаем поиск заново
                $this->absoluteMajority();
                break;
            } else {
                //Если остался последний кандидат(наихудший), то достаем его с массива кандидатов
                $candidate = array_shift($candidates);
                //Добавляем в массив коллективной ранжировки
                array_push($this->absoluteMajorityRank, $candidate);
                return $this->absoluteMajorityRank;
                break;
            }
        }
        //Обновим профиль для последующих методов
        $this->updateData();
        return $this->absoluteMajorityRank;
    }

    /**
     * Метод Симпсона
     * @return array
     */
    public function simpsonMethod():array
    {
        $candidatesArray = [];
        foreach ($this->data[0] as $candidates) {
            foreach ($candidates as $candidate) {
                array_push($candidatesArray, $candidate);
            }
            sort($candidatesArray);
            break;
        }

        /*
         * Строим матрицу попарных сравнений кандидатов между собой [S(a,x),S(b,x),S(c,x),S(d,x)].
         *  Содержит кол-во голосов, для которых кандидат (a,b,c,d) лучше за х
         */

        //Делаем сравниения между всеми кандидатами
        $sum = [];
        //Сравниваем каждого кандидата со всеми другими
        foreach ($candidatesArray as $candidate1) {
            $sum[$candidate1] = [];
            foreach ($candidatesArray as $candidate2) {
                //Если это один и тот же кандидат, пропускаем
                if ($candidate1 == $candidate2) continue;
                $s = 0;
                //Кол-во голосов, при которых candidate1 лучше чем candidate2
                $sum[$candidate1][$candidate2] = [];
                //Делаем обход по профилю
                for ($j = 0; $j < count($this->data); $j++) {
                    foreach ($this->data[$j] as $num => $candidates) {
                        //Индексы местоположения в матрице 1 и 2 кандидатов
                        $indexFirstCandidate = $indexSecondCandidate = 0;
                        for ($i = 0; $i < count($candidates); $i++) {
                            if ($candidate1 == $candidates[$i]) {
                                $indexFirstCandidate = $i;
                            }
                            if ($candidate2 == $candidates[$i]) {
                                $indexSecondCandidate = $i;
                            }
                        }
                        /*
                         * Если первый кандидат набрал больше голосов, чем второй(его индекс меньше чем у второго),то
                         * добавляем кол-во голосов, при которых 1 кандидат лучше второго
                         */
                        if ($indexFirstCandidate < $indexSecondCandidate) {
                            $s += $num;
                        }
                    }
                }
                $sum[$candidate1][$candidate2] = $s;
            }
        }
        var_dump($sum);

        //Найдем оценку Симпсона: S(a) = min S(a,x)
        $simpsonRate = [];
        foreach ($sum as $candidate => $candidates) {
            $simpsonRate[$candidate] = min($candidates);
        }
        //Получаем коллективную ранжировку
        arsort($simpsonRate);
        //Обновим профиль для последующих методов
        $this->updateData();
        return array_keys($simpsonRate);
    }


    /**
     * Метод последовательного исключения
     * @return array
     */
    public function consecutiveExclusion():array
    {
        $data = $this->data;
        //Создаем массив кандидатов
        $this->createArrayCandidates();
        //Получение суммы голосов для кандидатов, занявших первые места в индивидуальных предпочтениях избирателей
        for ($i = 0; $i < count($data); $i++) {
            foreach ($data[$i] as $num => $candidates) {
                $this->candidatesArray[$candidates[0]] += $num;
            }
        }

        //Определяем последовательно кандидата с наибольшим кол-вом голосов
        $winner = null;
        $winnerRate = 0;
        foreach ($this->candidatesArray as $candidate => $votes) {
            if ($winnerRate < $votes) {
                $winner = $candidate;
                $winnerRate = $votes;
            }
        }
        //Добавляем победителя в массив ранжировки
        array_push($this->consecutiveExclusionRank, $winner);

        //Удаляем из профиля кандидата-победителя
        for ($i = 0; $i < count($data); $i++) {
            foreach ($data[$i] as &$candidates) {
                foreach ($candidates as $index => $candidate) {
                    if ($candidate == $winner) {
                        unset($candidates[$index]);
                    }
                }
                $candidates = array_values($candidates);
            }
        }
        unset($candidates);
        var_dump($this->consecutiveExclusionRank);
        var_dump($data);
        echo '<hr>';
        $this->data = $data;
        if (count($this->consecutiveExclusionRank) < 4) {
            $this->consecutiveExclusion();
        }
        //Обновим профиль для последующих методов
        $this->updateData();
        return $this->consecutiveExclusionRank;
    }
}

