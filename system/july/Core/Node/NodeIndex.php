<?php

namespace July\Core\Node;

use App\Model;
use Illuminate\Support\Facades\DB;

class NodeIndex extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'node_index';

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'node_id',
        'field_id',
        'field_value',
        'langcode',
    ];

    /**
     * 存放经关键词分拆后的令牌
     *
     * @var array
     */
    protected $tokens = [];

    /**
     * 重建索引
     *
     * @return bool
     */
    public static function rebuild()
    {
        DB::beginTransaction();

        DB::delete('DELETE FROM node_index;');
        NodeField::searchableFields()->each(function (NodeField $field) {
            foreach (static::extractValueIndex($field) as $record) {
                DB::table('node_index')->insert($record);
            }
        });

        DB::commit();

        return true;
    }

    /**
     * 将指定字段的值转化为索引记录
     *
     * @param  \July\Core\Node\NodeField $field
     * @return array
     */
    protected static function extractValueIndex(NodeField $field)
    {
        $values = [];

        $key = $field->getKey();
        $field_type = $field->field_type_id;
        $weight = $field->weight;
        $columns = collect($field->getValueColumns())->pluck('name')->all();
        $nodeForeignKey = $field->getHostForeignKey();
        foreach ($field->getValueRecords() as $record) {
            foreach ($columns as $column) {
                if (empty($record[$column])) {
                    continue;
                }
                $values[] = [
                    'node_id' => $record[$nodeForeignKey],
                    'field_id' => $key,
                    'field_value' => static::prepareValue($record[$column], $field_type),
                    'langcode' => $record['langcode'],
                    'weight' => $weight,
                ];
            }
        }

        return $values;
    }

    /**
     * 净化 HTML 内容
     *
     * @param  string $content 字段内容
     * @param  string $type 内容类型
     * @return string
     */
    protected static function prepareValue($content, $type)
    {
        $content = preg_replace('/\s+/', ' ', $content);

        if ($type === 'html') {
            $blocks = [
                'div','p','h1','h2','h3','h4','h5','h6',
                'li','dt','dd','caption','th','td',
                'section','nav','header','article','aside','footer','menuitem','address',
                'br','hr',
            ];

            $content = preg_replace('/<('.implode('|', $blocks).')(?=\\s|>)/i', '; <$1', $content);
            $content = strip_tags($content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = preg_replace('/[\s;]+;/', ';', $content);
            $content = preg_replace('/([.,;?!]);\s/', '$1 ', $content);
        }

        return trim($content, ' ;');
    }

    /**
     * 在索引中检索指定的关键词
     *
     * @param  string $keywords 待检索的关键词
     * @return array
     */
    public static function search($keywords)
    {
        if (empty($keywords)) {
            return [
                'keywords' => $keywords,
                'results' => [],
            ];
        }

        $keywords = static::normalizeKeywords($keywords);

        $results = [];
        foreach (static::searchIndex($keywords) as $result) {
            $node_id = $result->node_id;
            $field_id = $result->field_id;

            $result = $result->toSearchResult($keywords);
            if (! isset($results[$node_id])) {
                $results[$node_id] = [
                    'node_id' => $node_id,
                    'weight' => 0,
                ];
            }
            $results[$node_id][$field_id] =  $result['content'];
            $results[$node_id]['weight'] +=  $result['weight'];
        }

        // 对结果排序
        array_multisort(
            array_column($results, 'weight'),
            SORT_DESC,
            array_column($results, 'node_id'),
            SORT_NUMERIC,
            $results
        );

        return [
            'keywords' => key($keywords),
            'results' => $results,
        ];
    }

    /**
     * 在索引中检索指定的关键词
     *
     * @param  array $keywords 关键词
     * @param  string|null $langcode
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    protected static function searchIndex(array $keywords, string $langcode = null)
    {
        $langcode = $langcode ?: langcode('frontend');

        $conditions = [];
        foreach ($keywords as $keyword => $weight) {
            $conditions[] = ['field_value', 'like', '%'.$keyword.'%', 'or'];
        }

        return static::query()
            ->where('langcode', $langcode)
            ->where(function ($query) use ($conditions) {
                $query->where($conditions);
            })
            ->get();
    }

    /**
     * 提取有效的关键词
     *
     * @param string $input
     * @return array
     */
    protected static function normalizeKeywords($input)
    {
        if (empty($input)) {
            return [];
        }

        if (strlen($input) > 100) {
            $input = substr($input, 0, 100);
        }

        $keywords = array_filter(preg_split('/\s+/', $input));
        $keywords = array_slice($keywords, 0, 10);
        $keywords = static::combineKeywords($keywords);
        arsort($keywords);

        return $keywords;
    }

    /**
     * 组合关键词，并标记权重
     *
     * @param array $keywords
     * @return array
     */
    public static function combineKeywords(array $keywords)
    {
        // 计算每个单词的权重，从左到右依次降低
        $wordWeights = [];
        foreach ($keywords as $index => $word) {
            $wordWeights[] = exp(-0.5*pow($index/3.82, 2));
        }

        // 将单词按顺序组合成查询短句，并计算每个短句的权重
        $combined = [];
        $offset = 0;
        while ($keywords) {
            $words = [];
            $weight = 0;
            foreach ($keywords as $index => $word) {
                $words[] = $word;
                $weight += $wordWeights[$offset + $index];
                $combined[implode(' ', $words)] = $weight;
            }

            $keywords = array_slice($keywords, 1);
            $offset++;
        }

        return $combined;

        // $weights = [];
        // $words = [];
        // foreach ($keywords as $index => $word) {
        //     $words[] = $word;
        //     $key = implode(' ', $words);
        //     $weights[$key] = pow(2, $index + 1) - $offset/10;
        // }

        // $keywords = array_slice($keywords, 1);
        // if ($keywords) {
        //     $weights = array_merge($weights, static::combineKeywords($keywords, $offset + 1));
        // }

        // return $weights;
    }

    /**
     * 将字段值转化为一条搜索结果
     *
     * @param  array $keywords
     * @return array
     */
    public function toSearchResult(array $keywords)
    {
        // $this->tokenize($keywords);
        $tokens = $this->tokenizer($keywords);

        $similar = $this->similar($this->attributes['field_value'], key($keywords));
        $weight = $this->weight*($this->attributes['weight'] ?? 1)*pow(10, pow($similar, 3));

        return [
            'content' => $this->joinTokens($tokens),
            'weight' => $weight,
        ];
    }

    /**
     * 使用关键词将字段值令牌化
     *
     * @param  array $keywords
     * @return array
     */
    protected function tokenizer(array $keywords)
    {
        $this->weight = 0;
        $content = trim($this->attributes['field_value']);
        $tokens = [];
        foreach ($keywords as $keyword => $weight) {
            $pos = stripos($content, $keyword);
            while ($pos !== false) {
                $tokens[] = substr($content, 0, $pos);

                $word = substr($content, $pos, strlen($keyword));
                if ($word !== $keyword) {
                    $weight *= 1 - str_diff($word, $keyword)*.5/strlen($keyword);
                }
                $this->weight += $weight;
                $tokens[] = '<span class="keyword">'.$word.'</span>';

                $content = substr($content, $pos + strlen($keyword));
                $pos = stripos($content, $keyword);
            }
        }
        if (!empty($content)) {
            $tokens[] = $content;
        }

        return $tokens;
    }

    /**
     * 将令牌拼合在一起
     *
     * @param  array $tokens
     * @return string
     */
    protected function joinTokens(array $tokens)
    {
        $content = trim($this->attributes['field_value']);
        if (strlen($content) <= 200) {
            return implode('', $tokens);
        }

        $pieces = [];
        $length = 0;
        for ($i=1; $i < count($tokens); $i+=2) {
            $left = $tokens[$i-1];
            if ($left) {
                $left = explode(' ', $left);
                $left = array_slice($left, -1*min(intval(count($left)/2), 5));
                $left = implode(' ', $left);
            }

            $right = $tokens[$i+1] ?? '';
            if ($right) {
                $right = explode(' ', $right);
                $right = array_slice($right, 0, min(intval(count($right)/2), 5));
                $right = implode(' ', $right);
            }

            $piece = trim($left.$tokens[$i].$right, '.,:;!?');
            $pieces[] = $piece;
            $length += strlen($piece) - strlen('<span class="keyword"></span>');

            if ($length >= 200) {
                break;
            }
        }

        return '... '.implode(' ... ', $pieces).' ...';
    }

    /**
     * 计算两个字符串的相似度（百分比）
     *
     * @param string $str1
     * @param string $str2
     * @return float
     */
    protected function similar($str1, $str2)
    {
        if ($str1 === $str2) {
			return 1;
		}

		$len1 = strlen($str1);
		$len2 = strlen($str2);
		if ($len1 === 0 || $len2 === 0) {
			return 0;
		}

		$maxlen = max($len1, $len2);
		if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
			return abs($len1 - $len2) / $maxlen;
        }

        // 长度相差 3 倍以上
        if ($len1/$len2 > 3 || $len1/$len2 < 1/3) {
            return 0;
        }

		$levenshtein = levenshtein($str1, $str2);
		$levenshtein -= ($levenshtein - levenshtein(strtolower($str1), strtolower($str2))) / 2;

		return ($maxlen - $levenshtein) / $maxlen;
    }
}
