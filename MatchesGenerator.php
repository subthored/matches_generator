<?php

$testData = '{
    "teams": [
    { "id": 1, "title": "Ливерпуль" },
    { "id": 2, "title": "Челси" },
    { "id": 3, "title": "Тоттенхэм Хотспур" },
    { "id": 4, "title": "Арсенал" },
    { "id": 5, "title": "Манчестер Юнайтед" },
    { "id": 6, "title": "Эвертон" },
    { "id": 7, "title": "Лестер Сити" },
    { "id": 8, "title": "Вест Хэм Юнайтед" },
    { "id": 9, "title": "Уотфорд" },
    { "id": 10, "title": "Борнмут" },
    { "id": 11, "title": "Бернли" },
    { "id": 12, "title": "Саутгемптон" },
    { "id": 13, "title": "Брайтон энд Хоув Альбион" },
    { "id": 14, "title": "Норвич Сити" },
    { "id": 15, "title": "Шеффилд Юнайтед" },
    { "id": 16, "title": "Фулхэм" },
    { "id": 17, "title": "Сток Сити" },
    { "id": 18, "title": "Мидлсбро" },
    { "id": 19, "title": "Суонси Сити" },
    { "id": 20, "title": "Дерби Каунти" }
  ]
}';

class DataLoader
// Загрузчик данных из JSON
{
    public static function load($source = null, $jsonString = null) {
        if ($jsonString) {
            return json_decode($jsonString, true);
        } elseif ($source) {
            if (filter_var($source, FILTER_VALIDATE_URL)) {
                $data = file_get_contents($source);
                return json_decode($data, true);
            } elseif (file_exists($source)) {
                $data = file_get_contents($source);
                return json_decode($data, true);
            }
        }
    }
}

class Team
// Каждая команда - объект с id и title
{
    public int $id;
    public string $title;

    public function __construct($id, $title) {
        $this->id = $id;
        $this->title = $title;
    }
}

class MatchesGenerator
{
    private $teams;
    private array $firstTour = [];
    private array $secondTour = [];

    public function __construct($teams) {
        $this->teams = $teams;
    }

    private function rotateTeams($teamIds, $halfCount) {
        // Ротация команд
        $firstTeam = array_shift($teamIds);
        array_splice($teamIds, $halfCount - 1, 0, $firstTeam);
        return $teamIds;
    }

    private function mirrorMatches($rounds): array {
        // Создание зеркальных матчей второго тура
        $mirroredMatches = [];
        foreach ($rounds as $round) {
            $mirroredRound = [];
            foreach ($round as $match) {
                $mirroredRound[] = [$match[1], $match[0]];
            }
            $mirroredMatches[] = $mirroredRound;
        }
        return $mirroredMatches;
    }

    private function findTeamById($id) {
        // Поиск названия команды по её id
        foreach ($this->teams as $team) {
            if ($team->id === $id) {
                return $team->title;
            }
        }
        return "Неизвестная команда";
    }

    private function displayRounds($rounds) {
        // Отображение туров
        foreach ($rounds as $roundNumber => $round) {
            echo "Тур " . ($roundNumber + 1) . "\n";
            foreach ($round as $match) {
                $homeTeam = $this->findTeamById($match[0]);
                $guestTeam = $this->findTeamById($match[1]);
                echo "$homeTeam против $guestTeam\n";
            }
            echo "\n";
        }
    }

    private function generateRandomRound(): array {
        // Рандомизация и генерация матчей для туров
        shuffle($this->teams);
        $teamsCount = count($this->teams);
        $roundsCount = $teamsCount - 1;
        $halfCount = $teamsCount / 2;
        $matches = [];
        $teamIds = array_map(fn($team) => $team->id, $this->teams);

        for ($round = 0; $round < $roundsCount; $round++) {
            shuffle($teamIds);
            $roundMatches = [];
            for ($i = 0; $i < $halfCount; $i++) {
                $homeTeamId = $teamIds[$i];
                $guestTeamId = $teamIds[$teamsCount - 1 - $i];
                $roundMatches[] = [$homeTeamId, $guestTeamId];
            }
            $matches[] = $roundMatches;
            $teamIds = $this->rotateTeams($teamIds, $halfCount);
        }

        return $matches;
    }

    public function displayMatches() {
        // Отображение кругов матчей
        echo "Первый круг:\n\n";
        $this->displayRounds($this->firstTour);

        echo "Второй круг:\n\n";
        $this->displayRounds($this->secondTour);
    }

    public function generateMatches() {
        // Вызов методов для генерации контента
        $this->firstTour = $this->generateRandomRound();
        $this->secondTour = $this->mirrorMatches($this->firstTour);
    }
}

try {
    $source = $argc > 1 ? $argv[1] : null;
    // В переменную $jsonString можно указать набор команд четного количества. Например для тестирования.
    $jsonString = null;
    $data = DataLoader::load($source, $jsonString);

    $teams = array_map(fn($teamData) => new Team($teamData['id'], $teamData['title']), $data['teams']);

    $matchesGenerator = new MatchesGenerator($teams);
    $matchesGenerator->generateMatches();
    $matchesGenerator->displayMatches();

} catch (Exception $e) {
    echo "Ошибка " . $e->getMessage() . "\n";
}
