class DeteksiModel {
  final String? createdAt;

  const DeteksiModel({
    this.createdAt,
  });

  factory DeteksiModel.fromJson(Map<String, dynamic> json) => DeteksiModel(
        createdAt: json['created_at'],
      );
}
