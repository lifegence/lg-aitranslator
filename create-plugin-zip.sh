#!/bin/bash

################################################################################
# LG AI Translator - Plugin ZIP Creator
#
# このスクリプトはWordPressプラグインをzip化します
#
# Usage: ./create-plugin-zip.sh
################################################################################

set -e  # エラーが発生したら即座に終了

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 設定
PLUGIN_NAME="lg-aitranslator"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="${SCRIPT_DIR}/${PLUGIN_NAME}"
OUTPUT_ZIP="${SCRIPT_DIR}/${PLUGIN_NAME}.zip"

# ヘッダー表示
echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}LG AI Translator - ZIP Creator${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# プラグインディレクトリの存在確認
if [ ! -d "$PLUGIN_DIR" ]; then
    echo -e "${RED}❌ エラー: プラグインディレクトリが見つかりません${NC}"
    echo -e "${RED}   場所: $PLUGIN_DIR${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} プラグインディレクトリ: ${PLUGIN_DIR}"

# 既存のzipファイルを削除
if [ -f "$OUTPUT_ZIP" ]; then
    echo -e "${YELLOW}⚠${NC}  既存のzipファイルを削除中..."
    rm -f "$OUTPUT_ZIP"
fi

# 除外するファイル/ディレクトリ
EXCLUDE_PATTERNS=(
    "*.git/*"
    "*.gitignore"
    "*.gitattributes"
    "*node_modules/*"
    "*vendor/*"
    "*.DS_Store"
    "*Thumbs.db"
    "*.idea/*"
    "*.vscode/*"
    "*.swp"
    "*.swo"
    "*~"
    "*.log"
    "*.tmp"
    "*.cache"
    "*package-lock.json"
    "*composer.lock"
    "*.env"
    "*.env.local"
    "*tests/*"
    "*test/*"
    "*__tests__/*"
    "*.github/*"
)

# 除外パターンを構築
EXCLUDE_ARGS=""
for pattern in "${EXCLUDE_PATTERNS[@]}"; do
    EXCLUDE_ARGS="$EXCLUDE_ARGS -x ${PLUGIN_NAME}/${pattern}"
done

# ZIP作成
echo -e "${YELLOW}📦${NC} ZIP作成中..."
cd "$SCRIPT_DIR"

if zip -r "$OUTPUT_ZIP" "$PLUGIN_NAME" $EXCLUDE_ARGS > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} ZIP作成完了"
else
    echo -e "${RED}❌ ZIP作成に失敗しました${NC}"
    exit 1
fi

# ファイルサイズ取得
FILE_SIZE=$(du -h "$OUTPUT_ZIP" | cut -f1)

# ZIP内容の確認
echo ""
echo -e "${BLUE}📋 ZIP内容の確認:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# 重要なファイルの存在確認
IMPORTANT_FILES=(
    "${PLUGIN_NAME}/lg-aitranslator.php"
    "${PLUGIN_NAME}/README.md"
    "${PLUGIN_NAME}/LICENSE"
    "${PLUGIN_NAME}/includes/class-gemini-translation-service.php"
    "${PLUGIN_NAME}/includes/class-openai-translation-service.php"
    "${PLUGIN_NAME}/includes/class-translation-cache.php"
    "${PLUGIN_NAME}/admin/class-admin-settings.php"
    "${PLUGIN_NAME}/admin/class-admin-ajax.php"
    "${PLUGIN_NAME}/admin/js/admin.js"
    "${PLUGIN_NAME}/admin/css/admin.css"
    "${PLUGIN_NAME}/assets/js/frontend.js"
    "${PLUGIN_NAME}/assets/css/frontend.css"
)

ALL_OK=true
for file in "${IMPORTANT_FILES[@]}"; do
    if unzip -l "$OUTPUT_ZIP" | grep -q "$file"; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file ${RED}(見つかりません)${NC}"
        ALL_OK=false
    fi
done

echo ""

# ファイル統計
TOTAL_FILES=$(unzip -l "$OUTPUT_ZIP" | tail -1 | awk '{print $2}')
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓${NC} 総ファイル数: ${TOTAL_FILES}"
echo -e "${GREEN}✓${NC} ファイルサイズ: ${FILE_SIZE}"
echo -e "${GREEN}✓${NC} 出力先: ${OUTPUT_ZIP}"

# 最終確認
echo ""
if [ "$ALL_OK" = true ]; then
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ ZIP作成成功！${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${BLUE}📤 WordPressへのインストール方法:${NC}"
    echo -e "   1. WordPressダッシュボード → プラグイン → 新規追加"
    echo -e "   2. 'プラグインのアップロード' をクリック"
    echo -e "   3. ${YELLOW}${OUTPUT_ZIP}${NC} を選択"
    echo -e "   4. '今すぐインストール' → '有効化'"
    echo -e "   5. 設定 → LG AI Translator で設定開始"
    echo ""
else
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}⚠️  警告: 一部のファイルが見つかりません${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}ZIPは作成されましたが、確認してください${NC}"
    echo ""
fi

# オプション: 詳細表示
echo -e "${BLUE}💡 ヒント:${NC}"
echo -e "   詳細なファイルリストを表示: ${YELLOW}unzip -l ${OUTPUT_ZIP}${NC}"
echo -e "   ZIP内容の検証: ${YELLOW}unzip -t ${OUTPUT_ZIP}${NC}"
echo ""

exit 0
