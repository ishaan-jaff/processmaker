<?php

namespace ProcessMaker\Ai\Handlers;

use OpenAI\Client;

class LanguageTranslationHandler extends OpenAiHandler
{
    protected $targetLanguage = 'Spanish';

    public function __construct()
    {
        parent::__construct();
        $this->config = [
            'model' => 'text-davinci-003',
            'max_tokens' => 1500,
            'temperature' => 0.7,
            'top_p' => 1,
            'n' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => 'END_',
        ];
    }

    public function setTargetLanguage($language) 
    {
        $this->targetLanguage = $language;
    }
    public function getPromptFile($type = null)
    {
        return file_get_contents($this->getPromptsPath() . 'language_translation_' . $type . '.md');
    }

    public function generatePrompt(String $type = null, String $json_list) : Object
    {
        $this->$json_list = $json_list;
        $prompt = $this->getPromptFile($type);
        $prompt = $this->replaceJsonList($prompt, $json_list);
        $prompt = $this->replaceLanguage($prompt, $this->targetLanguage);
        $prompt = $this->replaceStopSequence($prompt);
        $this->config['prompt'] = $prompt;

        return $this;
    }

    public function execute()
    {
        $client = app(Client::class);
        $response = $client
            ->completions()
            ->create(array_merge($this->getConfig()));

        return $this->formatResponse($response);
    }

    private function formatResponse($response)
    {
        $result = ltrim($response->choices[0]->text);
        $result = explode('Question:', $result)[0];
        $result = rtrim(rtrim(str_replace("\n", '', $result)));
        $result = str_replace('\'', '', $result);

        return [$result, $response->usage, $this->question];
    }

    public function replaceStopSequence($prompt)
    {
        $replaced = str_replace('{stopSequence}', $this->config['stop'] . " \n", $prompt);

        return $replaced;
    }

    public function replaceJsonList($prompt, $json_list)
    {
        $replaced = str_replace('{json_list}', $json_list . " \n", $prompt);

        return $replaced;
    }

    public function replaceLanguage($prompt, $language)
    {
        $replaced = str_replace('{language}', $language . " \n", $prompt);

        return $replaced;
    }

    public function prepareData($screens)
    {
        $notHtmlStrings = [];
        $htmlStrings = [];
        foreach ($screens as $screen) {
            $notHtmlStrings[$screen['id']] = [];
            $htmlStrings[$screen['id']] = [];
            foreach ($screen['availableStrings'] as $string) {
                if ($this->isHTML($string)) {
                    $htmlStrings[$screen['id']][] = $string;
                } else {
                    $notHtmlStrings[$screen['id']][] = $string;
                }
            }
        }

        return [json_encode($notHtmlStrings), json_encode($htmlStrings)];
    }

    public function isHTML($string) : bool
    {
        if ($string != strip_tags($string)){
            // is HTML
            return true;
        } else{
            // not HTML
            return false;
        }
    }

    public function chunkTranslations($translations) 
    {
        // 1000 tokens ~= 750words
        $totalWords = str_word_count($translations);
        $tokensUsage = $totalWords * 1000 / 750;

        dd($tokensUsage);
    }
}
