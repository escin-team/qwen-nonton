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
            
            // Ambil Stream URL dengan cache sangat singkat (30 detik) untuk menghindari stale data
            error_log('[WATCH] Calling getStreamUrl for ' . $provider . '/' . $id . '/' . $ep);
            $stream_res = $api->getStreamUrl($provider, $id, $ep, 30); // Cache hanya 30 detik
            $videoUrl = '';
            
            // Log raw response untuk debugging
            if ($stream_res === null) {
                error_log('[WATCH] Stream response is NULL');
            } else {
                error_log('[WATCH] Stream response type: ' . gettype($stream_res));
                error_log('[WATCH] Stream response keys: ' . implode(', ', is_array($stream_res) ? array_keys($stream_res) : 'N/A'));
            }
            
            // Ekstrak URL dari berbagai kemungkinan struktur JSON API
            if ($stream_res) {
                // FLICKREELS SPECIAL CASE: Response langsung punya 'hlsUrl' tanpa wrapper
                // Contoh: {"hlsUrl":"https://...","locked":false,"number":1}
                if (isset($stream_res['hlsUrl']) && !empty($stream_res['hlsUrl'])) {
                    $videoUrl = $stream_res['hlsUrl'];
                    error_log('[WATCH] Found hlsUrl in root: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['data']['hlsUrl']) && !empty($stream_res['data']['hlsUrl'])) {
                    $videoUrl = $stream_res['data']['hlsUrl'];
                    error_log('[WATCH] Found hlsUrl in data: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['data']['url']) && !empty($stream_res['data']['url'])) {
                    $videoUrl = $stream_res['data']['url'];
                    error_log('[WATCH] Found url in data: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['data']['stream_url']) && !empty($stream_res['data']['stream_url'])) {
                    $videoUrl = $stream_res['data']['stream_url'];
                    error_log('[WATCH] Found stream_url in data: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['data']['hls_url']) && !empty($stream_res['data']['hls_url'])) {
                    $videoUrl = $stream_res['data']['hls_url'];
                    error_log('[WATCH] Found hls_url in data: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['url']) && !empty($stream_res['url'])) {
                    $videoUrl = $stream_res['url'];
                    error_log('[WATCH] Found url in root: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['stream_url']) && !empty($stream_res['stream_url'])) {
                    $videoUrl = $stream_res['stream_url'];
                    error_log('[WATCH] Found stream_url in root: ' . substr($videoUrl, 0, 50));
                } elseif (isset($stream_res['hls']) && !empty($stream_res['hls'])) {
                    $videoUrl = $stream_res['hls'];
                    error_log('[WATCH] Found hls in root: ' . substr($videoUrl, 0, 50));
                } elseif (is_string($stream_res) && strpos($stream_res, '.m3u8') !== false) {
                    $videoUrl = $stream_res;
                    error_log('[WATCH] Response is string with m3u8');
                }
                
                // FALLBACK: Jika masih kosong, coba ekstrak dari response mentah
                if (empty($videoUrl) && is_array($stream_res)) {
                    // Cari key yang mengandung 'hls' atau 'url'
                    foreach ($stream_res as $key => $value) {
                        if (is_string($value) && strpos($value, '.m3u8') !== false) {
                            $videoUrl = $value;
                            error_log('[WATCH] Found m3u8 in key ' . $key);
                            break;
                        }
                        if (is_string($key) && stripos($key, 'hls') !== false && is_string($value)) {
                            $videoUrl = $value;
                            error_log('[WATCH] Found hls key: ' . $key);
                            break;
                        }
                        if (is_string($key) && stripos($key, 'url') !== false && is_string($value)) {
                            $videoUrl = $value;
                            error_log('[WATCH] Found url key: ' . $key);
                            break;
                        }
                    }
                }
            }

            if (empty($videoUrl)) {
                error_log('[WATCH] FINAL: videoUrl is EMPTY - Stream extraction failed');
            } else {
                error_log('[WATCH] FINAL: videoUrl found: ' . substr($videoUrl, 0, 50) . '...');
            }

            // Ambil detail drama untuk judul dan navigasi
            $detail_res = $api->getDramaDetail($provider, $id);
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
                'videoUrl' => $videoUrl,
                'provider' => $provider,
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
