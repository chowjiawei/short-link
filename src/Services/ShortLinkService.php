<?php


namespace Chowjiawei\ShortLink\Services;

use Chowjiawei\ShortLink\Models\ShortLink;

class ShortLinkService
{

    public function validateForbidPrefixUrl($url): bool
    {
        $forbidPrefixUrl=config('short-link.forbid_prefix');

        $parseUrl=parse_url($url);
        $string='/'.(explode('/', $parseUrl['path'])[1]??explode('/', $parseUrl['path'])[0]) ;
        foreach ($forbidPrefixUrl as $key => $value) {
            if ($string==$value) {
                return true;
            }
        }
        return false;
    }

    public function validateOldUrl($url = '')
    {
        if (!$url) {
            throw new \Exception('url必须有值', "422");
        }
        $parseUrl=parse_url($url);
        if ($this->validateForbidPrefixUrl($parseUrl['path'])) {
            throw new \Exception('含系统禁止前缀的url', "422");
        }

        //旧url只能是站内的，不可能是站外的
        $host=isset($parseUrl['host']) ? $parseUrl['host'] :'';
        $nowUrl=parse_url(config('app.url'));
        $hasHost=$host ? true :false;
        if ($hasHost) {
            if ($host!=$nowUrl['host']) {
                throw new \Exception('禁止使用站外url', "422");
            }
        }
        $oldUrl=isset($parseUrl['host']) ? $parseUrl['path'] :$url;
        $count=ShortLink::query()->where('redirect_old', $oldUrl)->count();
        if ($count >= 1) {
            throw new \Exception('该url已经被设置，不可重复', "422");
        }
    }


    //自定义 新链接 存入 链接关系表
    public function customShort($outOldUrl, $outNewUrl)
    {
        $this->validateOldUrl($outOldUrl);
        //为兼容绝对地址与相对地址的存储 与切换域名产生的影响，如果用户数据与当前域名一致，则使用相对地址，如果用户数据填写的是站外地址，则原样保存
        $parseUrl=parse_url($outOldUrl);
        $oldUrl=isset($parseUrl['scheme']) ? $parseUrl['path']  : $outOldUrl;
        $parseUrl=parse_url($outNewUrl);
        $newUrlHost=isset($parseUrl['host']) ? $parseUrl['host']  : null;
        if ($newUrlHost) {
            $parseUrl=parse_url(config('app.url'));
            if ($parseUrl['host']==$newUrlHost) {
                $newUrl=parse_url($outNewUrl)['path'];
            } else {
                $newUrl=$outNewUrl;
            }
        } else {
            $newUrl=$outNewUrl;
        }
        return ShortLink::create(['redirect_old'=>$oldUrl,'redirect_new'=>$newUrl]);
    }

    //系统 自分配 固定位数的新链接
    public function short($outOldUrl, $newUrlType, $length = 6)
    {
        $this->validateOldUrl($outOldUrl);
        if (!in_array($newUrlType, ['mix','number','minLetter','maxLetter'])) {
            throw new \Exception('类型错误', "422");
        }
        $parseUrl=parse_url($outOldUrl);
        $oldUrl=isset($parseUrl['scheme']) ? $parseUrl['path']  : $outOldUrl;

        if ($newUrlType=='mix') {
            //数字和字母 混合
            $newUrl=$this->getMixString($length);
        }
        if ($newUrlType=='number') {
            //数字
            $newUrl=$this->getMixString($length, 1);
        }
        if ($newUrlType=='minLetter') {
            //小写字母
            $newUrl=$this->getMixString($length, 2);
        }
        if ($newUrlType=='maxLetter') {
            //大写字母
            $newUrl=$this->getMixString($length, 3);
        }

        return ShortLink::create(['redirect_old'=>$oldUrl,'redirect_new'=>$newUrl]);
    }

    //在laravel http  controller中的fallback中加入此代码
    public function redirect()
    {
        $redirectUrlIsOpenStatus=config('short-link.enabled');
        if ($redirectUrlIsOpenStatus) {
            $url=parse_url(request()->getUri());
            $query=isset($url['query']) ? $url['query'] : '';
            $redirectUrl=$url['path'];
            $redirect=ShortLink::query()->where('redirect_old', $redirectUrl)->first();
            if ($redirect) {
                if ($query) {
                    $newUrl=$redirect->redirect_new.'?'.$query;
                } else {
                    $newUrl=$redirect->redirect_new;
                }
                return redirect($newUrl);
            }
        }
    }

    //1为 纯数字 2为 纯小写字母 3为 纯大写字母  默认为混合
    public function getMixString($length, $type = 4): string
    {
        $numberArray=["0", "1", "2", "3", "4", "5", "6", "7", "8", "9",];
        $minLetterArray=[ "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z"];
        $manLetterArray=[ "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z"];
        switch ($type) {
            case 1:
                $charsArray=$numberArray;
                break;
            case 2:
                $charsArray=$minLetterArray;
                break;
            case 3:
                $charsArray=$manLetterArray;
                break;
            default:
                $charsArray=array_values(array_merge($numberArray, $minLetterArray, $manLetterArray));
                break;
        }
        $charsLen = count($charsArray) - 1;
        while (true) {
            $result = "";
            for ($i=0; $i<$length; $i++) {
                $result .= $charsArray[mt_rand(0, $charsLen)];
            }
            $count=ShortLink::query()->where('redirect_new', $result)->first();
            if (!$count) {
                return $result;
            }
        }
    }

    //使用旧链接（长链接）删除
    public function deleteOldUrl($url)
    {
        ShortLink::query()->where('redirect_old', $url)->delete();
        return true;
    }
    //使用新链接（短链接）删除 这会删除全部相关的新链接
    public function deleteNewUrl($url)
    {
        $count=ShortLink::query()->where('redirect_new', $url)->delete();
        return true;
    }
}
