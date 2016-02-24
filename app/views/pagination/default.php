<div class="pagination">
	<ul class='clearfix' data-pagename='<?=$name?>'>
		<?php foreach($links as $page=>$link): ?>
			
			<?php if($page < 5): ?>
				<li><a data-page='<?=$page?>' href="<?=$link?>" class="<?=$page == $active ? $class : ''?>">Page <?=$page?></a></li>
				<?php if($active < 4 && $page == 4 && count($links) > 5): ?>
				<li><span class='paginate-divider'>...</span></li>
				<?php endif; ?>
			<?php elseif($page > count($links) - 4): ?>
				<?php if($active > count($links) - 3 && $page == count($links) - 3): ?>
				<li><span class="paginate-divider">...</span></li>
				<?php endif; ?>
				<li><a href="<?=$link?>" data-page='<?=$page?>' class="<?=$page == $active ? $class : ''?>">Page <?=$page?></a></li>
			<?php elseif($page == $active): ?>
				<li>
					<a href="<?=$link?>" data-page='<?=$page?>' class="<?=$page == $active ? $class : ''?>">Page <?=$page?></a>
				</li>
			<?php else: ?>
				<?php if($page + 1 == $active || $page - 1 == $active): ?>
					<?php if($page > 5 && $page > $page - 1 && $page < $active): ?>
					<li>
						<span class="paginate-divider">...</span>
					</li>
					<?php endif; ?>
					<li>
						<a href="<?=$link?>" data-page='<?=$page?>' class="<?=$page == $active ? $class : ''?>">Page <?=$page?></a>
					</li>
					<?php if($page < count($links) - 4 && $page <= $page + 1 && $page > $active): ?>
					<li>
						<span class="paginate-divider">...</span>
					</li>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</div>