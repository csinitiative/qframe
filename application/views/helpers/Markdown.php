<?php
/**
 * This file is part of the CSI RegQ.
 *
 * The CSI RegQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI RegQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class RegQ_View_Helper_Markdown {
  
  /**
   * Stores the markdown parser for later use (to avoid instantiating a new parser
   * for every call to this helper)
   * @var Markdown_Parser parser to use for rendering markdown'ed text
   */
  private $parser = null;
  
  /**
   * Renders the given text using Markdown
   *
   * @param  string plain text marked up with Markdown
   * @return string
   */
  public function markdown($text) {
    if(is_null($this->parser)) $this->parser = new Markdown_Parser();
    return $this->parser->transform($text);
  }
}
