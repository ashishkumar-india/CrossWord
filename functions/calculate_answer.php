<?php
/**
 * Calculate crossword answers - tracks both words and individual cells
 */
function calculateCrosswordAnswers($grid_data_json, $student_answers_json) {
    $grid_data = json_decode($grid_data_json, true);
    $student_answers = json_decode($student_answers_json, true);
    
    if (!is_array($grid_data) || !is_array($student_answers)) {
        return [
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
            'total' => 0,
            'correct_cells' => 0,
            'wrong_cells' => 0,
            'total_cells' => 0
        ];
    }
    
    // Extract all words from the grid
    $words = extractWordsFromGrid($grid_data);
    
    // Track word-level statistics
    $total_words = count($words);
    $correct_words = 0;
    $wrong_words = 0;
    
    // Track cell-level statistics
    $correct_cells = 0;
    $wrong_cells = 0;
    $total_cells = 0;
    
    // Count total fillable cells
    foreach ($grid_data as $row) {
        foreach ($row as $cell) {
            if (!$cell['isBlack']) {
                $total_cells++;
            }
        }
    }
    
    // Check each cell for correctness
    foreach ($grid_data as $i => $row) {
        foreach ($row as $j => $cell) {
            if (!$cell['isBlack']) {
                $key = "{$i}-{$j}";
                $student_answer = isset($student_answers[$key]) ? strtoupper(trim($student_answers[$key])) : '';
                $correct_answer = strtoupper($cell['letter']);
                
                if ($student_answer !== '') {
                    if ($student_answer === $correct_answer) {
                        $correct_cells++;
                    } else {
                        $wrong_cells++;
                    }
                }
            }
        }
    }
    
    // Check each word
    foreach ($words as $word) {
        $word_correct = true;
        $word_attempted = false;
        
        // Check each cell in the word
        foreach ($word['cells'] as $cell) {
            $key = "{$cell['row']}-{$cell['col']}";
            $student_answer = isset($student_answers[$key]) ? strtoupper(trim($student_answers[$key])) : '';
            $correct_answer = strtoupper($cell['letter']);
            
            if ($student_answer !== '') {
                $word_attempted = true;
            }
            
            if ($student_answer !== $correct_answer) {
                $word_correct = false;
            }
        }
        
        // Count the word result
        if ($word_attempted) {
            if ($word_correct) {
                $correct_words++;
            } else {
                $wrong_words++;
            }
        }
    }
    
    // Calculate score based on cells
    $score = $total_cells > 0 ? ($correct_cells / $total_cells) * 100 : 0;
    
    return [
        'score' => round($score, 2),
        'correct' => $correct_words,
        'wrong' => $wrong_words,
        'total' => $total_words,
        'correct_cells' => $correct_cells,
        'wrong_cells' => $wrong_cells,
        'total_cells' => $total_cells
    ];
}

/**
 * Extract all words (horizontal and vertical) from the crossword grid
 */
function extractWordsFromGrid($grid_data) {
    $words = [];
    $word_id = 0;
    
    // Extract horizontal words (across)
    for ($i = 0; $i < count($grid_data); $i++) {
        $current_word = [];
        
        for ($j = 0; $j < count($grid_data[$i]); $j++) {
            $cell = $grid_data[$i][$j];
            
            if (!$cell['isBlack'] && isset($cell['letter']) && $cell['letter'] !== '') {
                $current_word[] = [
                    'row' => $i,
                    'col' => $j,
                    'letter' => $cell['letter'],
                    'number' => $cell['number'] ?? null
                ];
            } else {
                if (count($current_word) > 1) {
                    $words[] = [
                        'id' => $word_id++,
                        'direction' => 'across',
                        'cells' => $current_word,
                        'number' => $current_word[0]['number']
                    ];
                }
                $current_word = [];
            }
        }
        
        if (count($current_word) > 1) {
            $words[] = [
                'id' => $word_id++,
                'direction' => 'across',
                'cells' => $current_word,
                'number' => $current_word[0]['number']
            ];
        }
    }
    
    // Extract vertical words (down)
    for ($j = 0; $j < count($grid_data[0]); $j++) {
        $current_word = [];
        
        for ($i = 0; $i < count($grid_data); $i++) {
            $cell = $grid_data[$i][$j];
            
            if (!$cell['isBlack'] && isset($cell['letter']) && $cell['letter'] !== '') {
                $current_word[] = [
                    'row' => $i,
                    'col' => $j,
                    'letter' => $cell['letter'],
                    'number' => $cell['number'] ?? null
                ];
            } else {
                if (count($current_word) > 1) {
                    $words[] = [
                        'id' => $word_id++,
                        'direction' => 'down',
                        'cells' => $current_word,
                        'number' => $current_word[0]['number']
                    ];
                }
                $current_word = [];
            }
        }
        
        if (count($current_word) > 1) {
            $words[] = [
                'id' => $word_id++,
                'direction' => 'down',
                'cells' => $current_word,
                'number' => $current_word[0]['number']
            ];
        }
    }
    
    return $words;
}
?>
