import javax.swing.*;
import java.awt.*;

public class PieChart extends JPanel {
	private double[] values;
	private Color[] colors;

	public PieChart(double[] values, Color[] colors) {
		this.values = values;
		this.colors = colors;
		setPreferredSize(new Dimension(400, 300));
	}

	@Override
	protected void paintComponent(Graphics g) {
		super.paintComponent(g);
		Graphics2D g2 = (Graphics2D) g;
		g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

		int total = 0;
		for (double v : values) total += v;

		int x = 50, y = 30, w = 220, h = 220;
		double start = 0.0;

		for (int i = 0; i < values.length; i++) {
			double angle = values[i] / total * 360.0;
			g2.setColor(colors[i % colors.length]);
			g2.fillArc(x, y, w, h, (int) Math.round(start), (int) Math.round(angle));
			start += angle;
		}

		// 凡例
		int lx = 300, ly = 50;
		for (int i = 0; i < values.length; i++) {
			g2.setColor(colors[i % colors.length]);
			g2.fillRect(lx, ly + i * 25, 15, 15);
			g2.setColor(Color.BLACK);
			String label = String.format("項目 %d: %.1f", i + 1, values[i]);
			g2.drawString(label, lx + 20, ly + 12 + i * 25);
		}
	}

	// 実行例
	public static void main(String[] args) {
		SwingUtilities.invokeLater(() -> {
			double[] data = {30, 15, 45, 10};
			Color[] cols = {Color.RED, Color.GREEN, Color.BLUE, Color.ORANGE};
			JFrame f = new JFrame("円グラフサンプル");
			f.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
			f.getContentPane().add(new PieChart(data, cols));
			f.pack();
			f.setLocationRelativeTo(null);
			f.setVisible(true);
		});
	}
}
