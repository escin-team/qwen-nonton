<?php
/**
 * DRAMA CONTROLLER - BULLETPROOF VERSION
 * Menangani Detail Drama dan Video Player dengan validasi ketat.
 */
class DramaController extends Controller {
    
    private $valid_providers = array(
        'dramabox', 'shortmax', 'reelshort', 'starshort', 
        'dramabite', 'goodshort', 'reelbuzz', 'freereels',
        'flickreels', 'microdrama', 'vigloo', 'dramawave',
        'netshort', 'idrama', 'melolo', 'velolo', 'stardusttv',
        'serialplus', 'dotdrama', 'rapidtv', 'shortswave',
        'dramanova', 'cubetv', 'flareflow', 'moboreels',
        'happyshort', 'reelife', 'pinedrama', 'flextv', 'reelala'
    );

    public function detail($provider, $id) {
        // 1. Validasi Provider
        if (!in_array(strtolower($provider), $this->valid_providers)) {
            $_SESSION['flash_error'] = 'Provider tidak valid.';
            redirect('/');
            return;
        }

        // 2. Validasi ID
        if (empty($id)) {
            $_SESSION['flash_error'] = 'ID Drama tidak ditemukan.';
            redirect('/');
            return;
        }

        try {
            $api = new ApiService();
            
            // Ambil Detail - Response sudah ternormalisasi: array('data' => [...])
            $detail_res = $api->getDramaDetail($provider, $id);
            $detail = array();

            // Ekstrak data dari response ternormalisasi
            if ($detail_res && isset($detail_res['data'])) {
                // Jika 'data' adalah array indexed (list), ambil item pertama
                if (isset($detail_res['data'][0])) {
                    $detail = $detail_res['data'][0];
                } else {
                    // Jika 'data' adalah object detail langsung
                    $detail = $detail_res['data'];
                }
            } elseif ($detail_res && is_array($detail_res) && !isset($detail_res['error'])) {
                // Fallback: response langsung tanpa wrapper
                $detail = $detail_res;
            }

            // Ambil Episodes - Response sudah ternormalisasi: array('data' => [...])
            $episodes_res = $api->getEpisodes($provider, $id);
            $episodes = array();

            // Ekstrak episodes dari response ternormalisasi
            if ($episodes_res && isset($episodes_res['data']) && is_array($episodes_res['data'])) {
                $episodes = $episodes_res['data'];
            } elseif ($episodes_res && is_array($episodes_res) && !isset($episodes_res['error'])) {
                // Fallback: jika response adalah array langsung
                $keys = array_keys($episodes_res);
                $isIndexedArray = (count($keys) > 0 && isset($keys[0]) && is_int($keys[0]));
                if ($isIndexedArray) {
                    $episodes = $episodes_res;
                }
            }

            // FALLBACK 1: Jika episodes masih kosong tapi detail punya 'episodes' array
            // Beberapa provider embed episodes di dalam detail response
            if (empty($episodes) && isset($detail['episodes']) && is_array($detail['episodes'])) {
                $episodes = $detail['episodes'];
            }

            // FALLBACK 2: Jika episodes masih kosong, coba ambil dari endpoint dengan cache lebih singkat
            if (empty($episodes)) {
                $episodes_res_fresh = $api->getEpisodes($provider, $id, 300);
                if ($episodes_res_fresh && isset($episodes_res_fresh['data']) && is_array($episodes_res_fresh['data'])) {
                    $episodes = $episodes_res_fresh['data'];
                } elseif ($episodes_res_fresh && is_array($episodes_res_fresh)) {
                    $keys = array_keys($episodes_res_fresh);
                    $isIndexedArray = (count($keys) > 0 && isset($keys[0]) && is_int($keys[0]));
                    if ($isIndexedArray) {
                        $episodes = $episodes_res_fresh;
                    }
                }
            }

            // Pastikan minimal ada ID dan Title, jika tidak, anggap drama tidak ditemukan
            if (empty($detail) || (isset($detail['error']))) {
                $_SESSION['flash_error'] = 'Drama tidak ditemukan di provider ' . strtoupper($provider);
                redirect('/');
                return;
            }

            // LAST RESORT: Jika episodes masih kosong, coba ekstrak dari detail response
            if (empty($episodes) && isset($detail['episodes'])) {
                if (is_array($detail['episodes'])) {
                    $episodes = $detail['episodes'];
                }
            }

            $site_name = defined('SITE_NAME') ? SITE_NAME : 'Nontonin';
            $title = isset($detail['title']) ? $detail['title'] : 'Detail Drama';

            // Kirim ke View
            $this->view('drama/detail', array(
                'detail' => $detail,
                'episodes' => $episodes,
                'provider' => $provider,
                'drama_id' => $id,
                'title' => $title . ' - ' . $site_name
            ));

        } catch (Exception $e) {
            error_log('Drama Detail Error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Terjadi kesalahan saat memuat data drama.';
            redirect('/');
        }
    }

    public function watch($provider, $id, $ep = 1) {
        // 1. Validasi Provider
        if (!in_array(strtolower($provider), $this->valid_providers)) {
            redirect('/');
            return;
        }

        // 2. Validasi Parameter
        if (empty($id) || empty($ep)) {
            redirect('/');
            return;
        }

        try {
            $api = new ApiService();
            
            $streamUrl = null;
            $usedProvider = $provider;
            $fallbackProviders = array('flickreels', 'dramabos', 'bilibili', 'freereels', 'wetv', 'iqiyi');
            
            error_log("[WATCH] Trying primary provider: {$provider}");
            $stream_res = $api->getStreamUrl($provider, $id, $ep, 30);
            
            // Ekstrak URL dari response
            if ($stream_res) {
                if (isset($stream_res['hlsUrl']) && !empty($stream_res['hlsUrl'])) {
                    $streamUrl = $stream_res['hlsUrl'];
                } elseif (isset($stream_res['data']['hlsUrl']) && !empty($stream_res['data']['hlsUrl'])) {
                    $streamUrl = $stream_res['data']['hlsUrl'];
                } elseif (isset($stream_res['data']['url']) && !empty($stream_res['data']['url'])) {
                    $streamUrl = $stream_res['data']['url'];
                } elseif (isset($stream_res['url']) && !empty($stream_res['url'])) {
                    $streamUrl = $stream_res['url'];
                } elseif (is_string($stream_res) && strpos($stream_res, '.m3u8') !== false) {
                    $streamUrl = $stream_res;
                }
            }
            
            if (empty($streamUrl)) {
                error_log("[WATCH] Primary provider failed. Trying fallbacks...");
                
                foreach ($fallbackProviders as $fallback) {
                    if ($fallback === $provider) continue;
                    
                    error_log("[WATCH] Trying fallback provider: {$fallback}");
                    $stream_res = $api->getStreamUrl($fallback, $id, $ep, 30);
                    
                    if ($stream_res) {
                        if (isset($stream_res['hlsUrl']) && !empty($stream_res['hlsUrl'])) {
                            $streamUrl = $stream_res['hlsUrl'];
                        } elseif (isset($stream_res['data']['hlsUrl']) && !empty($stream_res['data']['hlsUrl'])) {
                            $streamUrl = $stream_res['data']['hlsUrl'];
                        } elseif (isset($stream_res['data']['url']) && !empty($stream_res['data']['url'])) {
                            $streamUrl = $stream_res['data']['url'];
                        } elseif (isset($stream_res['url']) && !empty($stream_res['url'])) {
                            $streamUrl = $stream_res['url'];
                        } elseif (is_string($stream_res) && strpos($stream_res, '.m3u8') !== false) {
                            $streamUrl = $stream_res;
                        }
                    }
                    
                    if (!empty($streamUrl)) {
                        $usedProvider = $fallback;
                        error_log("[WATCH] Fallback SUCCESS: {$fallback}");
                        break;
                    }
                }
            }
            
            if (empty($streamUrl)) {
                error_log("[WATCH] ALL PROVIDERS FAILED for episode {$ep}");
                $this->view('player/error', array(
                    'title' => 'Video Tidak Tersedia',
                    'message' => 'Maaf, video sedang tidak dapat diputar saat ini. Silakan coba lagi beberapa saat lagi atau pilih episode lain.',
                    'episode' => $ep
                ));
                return;
            }
            
            error_log('[WATCH] FINAL: videoUrl found: ' . substr($streamUrl, 0, 50) . '...');

            // Ambil detail drama untuk judul dan navigasi
            $detail_res = $api->getDramaDetail($usedProvider, $id);
            $detail = array();
            if ($detail_res) {
                if (isset($detail_res['data']) && is_array($detail_res['data'])) {
                    $detail = $detail_res['data'];
                } elseif (is_array($detail_res) && !isset($detail_res['error'])) {
                    $detail = $detail_res;
                }
            }

            $site_name = defined('SITE_NAME') ? SITE_NAME : 'Nontonin';
            $drama_title = isset($detail['title']) ? $detail['title'] : 'Streaming';

            $this->view('player/watch', array(
                'videoUrl' => $streamUrl,
                'provider' => $usedProvider,
                'drama_id' => $id,
                'episode' => (int)$ep,
                'detail' => $detail,
                'title' => 'Nonton ' . $drama_title . ' Episode ' . $ep . ' - ' . $site_name
            ));

        } catch (Exception $e) {
            error_log('Drama Watch Error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Gagal memuat video player.';
            redirect('/');
        }
    }
}
